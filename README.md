# Magento 2 Customer Orders Count Module

This Magento 2 module adds a **"Number of Orders"** column to the **Customers > All Customers** grid in the Admin panel, showing the total number of **completed** (`complete` status) orders per customer.  
Importantly, it does **not** join the `sales_order` table directly in the main customer collection. Instead, it uses a **separate** query in an `afterLoad` plugin to fetch and map these counts.

## Features

1. **Separate Query**  
   Retrieves the completed orders count from `sales_order` by grouping on `customer_id`.

2. **No Sorting/Filtering**  
   Since it’s a separate lookup and not an actual join, the new column is purely informational. You **cannot** sort or filter by it.

3. **Performance-Friendly**  
   Avoiding a join on the customer grid prevents performance degradation and sorting/filtering complexities.

4. **One-Time Flag**  
   Prevents infinite recursion by setting a custom flag on the collection, ensuring the plugin’s logic runs only once.

## How It Works

- The module defines a plugin (`afterLoad`) on `Magento\Customer\Model\ResourceModel\Grid\Collection`.  
- After the main customer collection is loaded, it fetches all **customer IDs**, then performs a separate SQL query to the `sales_order` table to count how many orders each customer has with status = `complete`.  
- It maps these counts back into each customer’s data as `orders_count`.  
- Finally, the `customer_listing.xml` adds a new column named **Number of Orders** to display that value in the grid.

### Avoiding Infinite Recursion

In Magento 2, calling or re-calling `load()` on the same collection (or performing certain actions that inadvertently trigger `load()`) can cause an infinite loop in an `afterLoad` plugin. To prevent this, we use a “one-time” flag. Specifically:

1. **Check the collection for a custom flag** (e.g. `orders_count_column_added`) at the start of the plugin. If it’s set, we return immediately.
2. **Set this flag** on the collection as soon as the plugin runs, ensuring that even if something tries to re-invoke the `afterLoad` event, the logic won’t run again.

## Requirements

- Magento 2.3.x or later (tested up through Magento 2.4.x).
- PHP 7.4+ / PHP 8.x

## Installation

1. **Download or clone** the repository to your Magento 2 `app/code/` directory:

    ```bash
    cd <magento-root>/app/code
    git clone https://github.com/<your-repo>/CustomerOrdersCount.git Magendoo/CustomerOrdersCount
    ```

    Ensure the folder structure matches `Magendoo/CustomerOrdersCount`.

2. **Enable the module**:

    ```bash
    bin/magento module:enable Magendoo_CustomerOrdersCount
    ```

3. **Upgrade setup**:

    ```bash
    bin/magento setup:upgrade
    ```

4. **Clear/Flush cache** (optional but recommended):

    ```bash
    bin/magento cache:clean
    bin/magento cache:flush
    ```

## Usage

1. **Navigate to Admin** > **Customers** > **All Customers**.  
2. You will see a new column labeled **Number of Orders**.  
3. The column shows the total of **completed** (`complete`) orders for each customer.

> **Note**: This column is **not** sortable or filterable, as it’s not actually part of the main collection query.

## Troubleshooting

- **Infinite Recursion**:  
  If you encounter a `Maximum call stack size reached` error, ensure the plugin uses a **flag** (e.g., `setFlag('orders_count_column_added', true)`) inside the `afterLoad` method. This prevents multiple invocations of the same logic.

- **No Data in Column**:  
  - Ensure your store has actual completed orders for the test customers.  
  - Verify that `status = complete` is indeed your “completed” status.  
  - Confirm the plugin is enabled and the module is properly installed.

## Contributing

Feel free to open issues or pull requests if you find bugs or have improvements.

## License

This project is open-sourced under the [MIT license](LICENSE).
