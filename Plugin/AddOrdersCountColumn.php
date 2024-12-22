<?php
namespace Magendoo\CustomerOrdersCount\Plugin;

use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;
use Magento\Framework\App\ResourceConnection;

/**
 * Plugin to add "orders_count" data to each customer in the Customer Grid
 * by performing a separate query after the grid collection has loaded.
 */
class AddOrdersCountColumn
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Constructor
     *
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * After the customer grid collection is loaded, fetch the number of
     * completed orders for each customer and set it to "orders_count".
     *
     * @param CustomerGridCollection $collection
     * @return CustomerGridCollection
     */
    public function afterLoad(CustomerGridCollection $collection)
    {
        // Check if we’ve already added the orders_count to avoid re-running
        if ($collection->getFlag('orders_count_column_added')) {
            return $collection; // exit early if the flag is set
        }

        // Mark that we've handled this collection
        $collection->setFlag('orders_count_column_added', true);
                
        // 1. Gather all customer IDs from the loaded collection
        $customerIds = [];
        foreach ($collection as $item) {
            $customerIds[] = $item->getEntityId();
        }

        // 2. If no customers in collection, return immediately
        if (empty($customerIds)) {
            return $collection;
        }

        // 3. Run a separate query against sales_order to get the number of completed orders
        $connection = $this->resource->getConnection();
        $salesOrderTable = $this->resource->getTableName('sales_order');

        $select = $connection->select()
            ->from(
                $salesOrderTable,
                [
                    'customer_id',
                    new \Zend_Db_Expr('COUNT(*) AS orders_count'),
                ]
            )
            ->where('customer_id IN (?)', $customerIds)            
            ->group('customer_id');

        // fetchPairs: returns array(customer_id => orders_count)
        $counts = $connection->fetchPairs($select);

        // 4. Map these counts back into the collection items
        foreach ($collection as $customer) {
            $customerId = $customer->getEntityId();
            $customer->setData(
                'orders_count',
                $counts[$customerId] ?? 0
            );
        }

        return $collection;
    }
}
