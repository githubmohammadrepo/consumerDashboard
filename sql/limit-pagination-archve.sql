(
  SELECT NewTable.*,
    pish_phocamaps_marker_store.ShopName
  FROM (
      SELECT *
      FROM (
          SELECT pish_customer_vendor.id,
            pish_customer_vendor.customer_id,
            pish_customer_vendor.vendor_id,
            pish_customer_vendor.order_id AS customer_order_id,
            pish_customer_vendor.buy_status,
            pish_customer_vendor.archive,
            pish_customer_vendor.proposal_completed,
            pish_customer_vendor.customer_archived,
            pish_customer_vendor.order_id AS custome_order_id
          FROM `pish_customer_vendor`
          order by pish_customer_vendor.order_id
          limit 0, 10
        ) AS customer_vendor
        INNER JOIN pish_hikashop_order_product ON customer_vendor.customer_order_id = pish_hikashop_order_product.order_id
        AND customer_vendor.vendor_id = pish_hikashop_order_product.vendor_id_accepted
      WHERE customer_vendor.buy_status = 'done'
        AND customer_vendor.customer_archived IS NOT NULL
        AND customer_vendor.customer_id = (
          SELECT `user_id`
          FROM pish_hikashop_user
          WHERE user_cms_id = 963
          LIMIT 1
        )
    ) as NewTable
    INNER JOIN pish_phocamaps_marker_store ON NewTable.vendor_id = pish_phocamaps_marker_store.id
)
UNION
(
  SELECT NewTable.*,
    pish_phocamaps_marker_store.ShopName
  FROM (
      SELECT *
      FROM (
          SELECT pish_customer_vendor.id,
            pish_customer_vendor.customer_id,
            pish_customer_vendor.vendor_id,
            pish_customer_vendor.order_id AS customer_order_id,
            pish_customer_vendor.buy_status,
            pish_customer_vendor.archive,
            pish_customer_vendor.proposal_completed,
            pish_customer_vendor.order_id AS custome_order_id,
            pish_customer_vendor.customer_archived
          FROM `pish_customer_vendor`
          order by pish_customer_vendor.order_id
          limit 0, 10
        ) AS customer_vendor
        INNER JOIN proposal_order_product ON customer_vendor.customer_order_id = proposal_order_product.order_id
        AND customer_vendor.vendor_id = proposal_order_product.vendor_id_accepted
      WHERE customer_vendor.buy_status = 'proposal'
        AND customer_vendor.customer_archived IS NOT NULL
        AND customer_vendor.customer_id = (
          SELECT `user_id`
          FROM pish_hikashop_user
          WHERE user_cms_id = 963
          LIMIT 1
        )
    ) AS NewTable
    INNER JOIN pish_phocamaps_marker_store ON NewTable.vendor_id = pish_phocamaps_marker_store.id
)