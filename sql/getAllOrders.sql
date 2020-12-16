 -- # section one
SELECT  pish_customer_vendor.*
       ,pish_hikashop_order_product.*
FROM `pish_customer_vendor`
INNER JOIN pish_hikashop_order_product
ON pish_customer_vendor.order_id = pish_hikashop_order_product.order_id
WHERE pish_customer_vendor.buy_status = 'done' 
AND pish_customer_vendor.customer_id = 800 
union 
SELECT  pish_customer_vendor.*
       ,proposal_order_product.*
FROM `pish_customer_vendor`
INNER JOIN proposal_order_product
ON pish_customer_vendor.order_id = proposal_order_product.order_id
WHERE pish_customer_vendor.buy_status = 'proposal' 
AND pish_customer_vendor.customer_id = 800 


-- #union section oen and two