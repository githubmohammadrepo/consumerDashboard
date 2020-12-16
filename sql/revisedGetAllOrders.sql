SELECT  tableUnioned.*
FROM 
(
	SELECT  pish_customer_vendor.id 
	       ,pish_customer_vendor.customer_id 
	       ,pish_customer_vendor.vendor_id
	       ,pish_customer_vendor.order_id AS customer_order_id 
	       ,pish_customer_vendor.buy_status archive 
	       ,pish_customer_vendor.proposal_completed 
	       ,pish_customer_vendor.order_id AS custome_order_id 
	       ,pish_hikashop_order_product.*
	FROM `pish_customer_vendor`
	INNER JOIN pish_hikashop_order_product
	ON pish_customer_vendor.order_id = pish_hikashop_order_product.order_id
	WHERE pish_customer_vendor.buy_status = 'done' 
	AND pish_customer_vendor.customer_id = 800 UNION 
	SELECT  pish_customer_vendor.id 
	       ,pish_customer_vendor.customer_id 
	       ,pish_customer_vendor.vendor_id
	       ,pish_customer_vendor.order_id 
	       ,pish_customer_vendor.buy_status archive 
	       ,pish_customer_vendor.proposal_completed 
	       ,pish_customer_vendor.order_id AS custome_order_id 
	       ,proposal_order_product.*
	FROM `pish_customer_vendor`
	INNER JOIN proposal_order_product
	ON pish_customer_vendor.order_id = proposal_order_product.order_id
	WHERE pish_customer_vendor.buy_status = 'proposal' 
	AND pish_customer_vendor.customer_id = 800  
) AS tableUnioned 
ORDER BY tableUnioned.order_id