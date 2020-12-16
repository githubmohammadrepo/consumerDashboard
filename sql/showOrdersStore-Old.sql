SELECT pish_customer_vendor.*,
  pish_hikashop_order_product.*
FROM pish_customer_vendor
  INNER JOIN pish_hikashop_order_product ON pish_customer_vendor.order_id = pish_hikashop_order_product.order_id
WHERE pish_customer_vendor.vendor_id = 128141
  AND pish_customer_vendor.archive is null
  AND pish_customer_vendor.buy_status != 'proposal'
)
UNION
(
  SELECT pish_customer_vendor.*,
    proposal_order_product.*
  FROM pish_customer_vendor
    INNER JOIN proposal_order_product ON pish_customer_vendor.order_id = proposal_order_product.order_id
  WHERE pish_customer_vendor.vendor_id = 128141
    AND pish_customer_vendor.archive is null
    AND pish_customer_vendor.buy_status = 'proposal
