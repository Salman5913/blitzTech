-- POS menu (Restaurant > Website Menu > POS), per branch, kept apart from the website menu:
-- categories hidden from the POS screen, and items hidden or not available on it.
-- Everything is shown and available on POS unless listed here. attribute_id 0 = item without variation.
CREATE TABLE IF NOT EXISTS rs_pos_hidden_categories (
    branch_id INT NOT NULL,
    stock_category_id INT NOT NULL,
    PRIMARY KEY (branch_id, stock_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS rs_pos_items (
    branch_id INT NOT NULL,
    stock_id INT NOT NULL,
    attribute_id INT NOT NULL DEFAULT 0,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    is_unavailable TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (branch_id, stock_id, attribute_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
