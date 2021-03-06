<?php

namespace App\Orders\Repositories;

use App\Base\BaseRepository;
use App\Orders\Exceptions\OrderInvalidArgumentException;
use App\Orders\Exceptions\OrderNotFoundException;
use App\Orders\Order;
use App\Orders\Repositories\Interfaces\OrderRepositoryInterface;
use App\Products\Product;
use App\Products\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    /**
     * Create the order
     *
     * @param array $params
     * @return Order
     * @throws OrderInvalidArgumentException
     */
    public function createOrder(array $params) : Order
    {
        try {
            return $this->create($params);
        } catch (QueryException $e) {
            throw new OrderInvalidArgumentException($e->getMessage(), 500, $e);
        }
    }

    /**
     * @param array $params
     * @return Order
     * @throws OrderInvalidArgumentException
     */
    public function updateOrder(array $params) : Order
    {
        try {
            $this->update($params, $this->model->id);
            return $this->find($this->model->id);
        } catch (QueryException $e) {
            throw new OrderInvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @param int $id
     * @return Order
     * @throws OrderNotFoundException
     */
    public function findOrderById(int $id) : Order
    {
        try {
            return $this->findOneOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new OrderNotFoundException($e->getMessage());
        }
    }


    /**
     * Return all the orders
     *
     * @param string $order
     * @param string $sort
     * @param array $columns
     * @return Collection
     */
    public function listOrders(string $order = 'id', string $sort = 'desc', array $columns = ['*']) : Collection
    {
        return $this->all($columns, $order, $sort);
    }

    /**
     * @param Order $order
     * @return mixed
     */
    public function findProducts(Order $order)
    {
        return $order->products;
    }

    /**
     * @param Product $product
     * @param int $quantity
     */
    public function associateProduct(Product $product, int $quantity = 1)
    {
        $this->model->products()->attach($product, ['quantity' => $quantity]);

        $this->updateProductQuantity($product, $quantity);
    }

    /**
     * @param $product
     * @param $qty
     * @return Product
     */
    private function updateProductQuantity($product, $qty)
    {
        // update the product quantity
        $productRepo = new ProductRepository($product);

        $quantity = $product->quantity - $qty;
        $productRepo->updateProduct(compact('quantity'));
    }
}