SELECT  *
FROM pish_hikashop_order
INNER JOIN pish_hikashop_order_product
ON pish_hikashop_order.order_id = pish_hikashop_order_product.order_id UNION
SELECT  *
FROM pish_hikashop_order
INNER JOIN proposal_order_product
ON pish_hikashop_order.order_id = proposal_order_product.order_id