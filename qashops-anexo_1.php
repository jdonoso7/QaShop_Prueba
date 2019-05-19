<?php

class Product
{
    public static function stock(
        $productId,
        $quantityAvailable,
        $cache = false,
        $cacheDuration = 60,
        $securityStockConfig = null
    ) {
        if ($cache) {
            // Obtenemos el stock bloqueado por pedidos en curso
            $this->getBlockedStockOrder (true, $cacheDuration, $db, $productId);

            // Obtenemos el stock bloqueado
            $this->getBlokeckOrder (true, $cacheDuration, $db, $productId);
        } else {
            // Obtenemos el stock bloqueado por pedidos en curso
            $this->getBlockedStockOrder (false, $cacheDuration);
            // Obtenemos el stock bloqueado
            $this->getBlokeckOrder (false, $cacheDuration);
        }

        // Calculamos las unidades disponibles
        if (isset($ordersQuantity) || isset($blockedStockQuantity)) {

            //  Calculamos la cantidad teniendo en cuenta que hay stock bloqueado
            return $this->getCuantity(true, $quantityAvailable, $securityStockConfig);
        } else {
            //  Calculamos la cantidad teniendo en cuenta que no hay stock bloqueado
            return $this->getCuantity(false, $quantityAvailable,  $securityStockConfig);
        }
        return 0;
    }

    protected function getBlockedStockOrder ($cache, $cacheDuration, $db=null, $productId=null) {
        if($cache) {
            $ordersQuantity = OrderLine::getDb()->cache(function ($db) use ($productId) {
                    return OrderLine::find()->select('SUM(quantity) as quantity')
                                            ->joinWith('order')
                                            ->where("(order.status = '" . Order::STATUS_PENDING . "' OR order.status = '" . Order::STATUS_PROCESSING . "' OR order.status = '" . Order::STATUS_WAITING_ACCEPTANCE . "') AND order_line.product_id = $productId")
                                            ->scalar();
                }, $cacheDuration);
        } else {
            $ordersQuantity = OrderLine::find()->select('SUM(quantity) as quantity')
                                               ->joinWith('order')
                                               ->where("(order.status = '" . Order::STATUS_PENDING . "' OR order.status = '" . Order::STATUS_PROCESSING . "' OR order.status = '" . Order::STATUS_WAITING_ACCEPTANCE . "') AND order_line.product_id = $productId")
                                               ->scalar();
        }
    }

    protected function getBlokeckOrder ($cache, $db=null, $productId=null) {
        if($cache) {
            $blockedStockQuantity = BlockedStock::getDb()->cache(function ($db) use ($productId) {
                    return BlockedStock::find()->select('SUM(quantity) as quantity')
                    ->joinWith('shoppingCart')
                    ->where("blocked_stock.product_id = $productId AND blocked_stock_date > '" . date('Y-m-d H:i:s') . "' AND (shopping_cart_id IS NULL OR shopping_cart.status = '" . ShoppingCart::STATUS_PENDING . "')")
                    ->scalar();
                }, $cacheDuration);
        } else {
            $blockedStockQuantity = BlockedStock::find()->select('SUM(quantity) as quantity')
                                                        ->joinWith('shoppingCart')
                                                        ->where("blocked_stock.product_id = $productId AND blocked_stock_to_date > '" . date('Y-m-d H:i:s') . "' AND (shopping_cart_id IS NULL OR shopping_cart.status = '" . ShoppingCart::STATUS_PENDING . "')")
                                                        ->scalar();
        }
    }

    protected function getCuantity ($stockBloqued, $quantityAvailable, $securityStockConfig = null) {
        if ($quantityAvailable >= 0) {
            if($stockBloqued){
                $quantity = $quantityAvailable - @$ordersQuantity - @$blockedStockQuantity;
            }
            if (!empty($securityStockConfig)) {
                $quantity = ShopChannel::applySecurityStockConfig(
                    $quantity,
                    @$securityStockConfig->mode,
                    @$securityStockConfig->quantity
                );
            }
            if($stockBloqued){
                return $quantity > 0 ? $quantity : 0;
            }else{
                return $quantityAvailable;
            }
        } elseif ($quantityAvailable < 0) {
            return $quantityAvailable;
        }
    }
}

