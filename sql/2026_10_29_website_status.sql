-- Restaurant website On / Off (Restaurant > Website On/Off). One row (id = 1) for the whole website.
--   is_open = 0                 : closed until switched on again
--   is_open = 1, no times       : always open
--   is_open = 1, open/close time: open every day between those times (close before open = past midnight)
-- While closed the website shows a popup ("Kindly visit at 6:00 PM") and the API refuses new orders.
CREATE TABLE IF NOT EXISTS rs_website_settings (
    id TINYINT NOT NULL PRIMARY KEY,
    is_open TINYINT(1) NOT NULL DEFAULT 1,
    open_time TIME NULL,
    close_time TIME NULL,
    closed_message VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO rs_website_settings (id, is_open) VALUES (1, 1);
