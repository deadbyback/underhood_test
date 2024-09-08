SELECT
    p.date AS date,
    SUM(p.quantity * pl.price) AS total_value
FROM
    products p
        JOIN LATERAL (
            SELECT
                pl.price
            FROM
                price_log pl
            WHERE
                pl.product_id = p.product_id
              AND pl.date <= p.date
            ORDER BY
                pl.date DESC
            LIMIT 1
        ) pl ON TRUE
WHERE
    p.date BETWEEN '2020-01-01' AND '2020-01-10'
GROUP BY
    p.date
ORDER BY
    p.date;

