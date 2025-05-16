create database mktrip;
use mktrip;

CREATE TABLE Users (
    usr_id INT AUTO_INCREMENT PRIMARY KEY,
    usr_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user' NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
create TABLE Tour_Guides (
    guide_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) CHECK (phone REGEXP '^[0-9]{10,15}$'),
    language VARCHAR(255),
    price int NOT NULL,
    experience INT,
    destination_id int,
    img_url VARCHAR(255),
    rating DECIMAL(2,1) DEFAULT 0.0,
    foreign key (destination_id ) references Destinations(destination_id) on delete cascade
);
CREATE TABLE Destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    image varchar(255)
);
create TABLE Tours (
    tour_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_name VARCHAR(255) NOT NULL,
    destination_id INT NOT NULL,
    price int NOT NULL,
    days INT NOT NULL,
    start_date DATE,
    end_date DATE,
    max_guests INT NOT NULL,
    available_slots INT NOT NULL,
    description text,
    rating DECIMAL(2,1) DEFAULT 0.0,
    image_url VARCHAR(255),
    FOREIGN KEY (destination_id) REFERENCES Destinations(destination_id) on delete cascade
);

create TABLE guide_bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    usr_id INT NOT NULL,
    guide_id INT NOT NULL,
    booking_date DATE NOT NULL,
    days INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
	status ENUM('pending','confirmed', 'paid', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usr_id) REFERENCES Users(usr_id) ON DELETE CASCADE,
    FOREIGN KEY (guide_id) REFERENCES Tour_Guides(guide_id) ON DELETE CASCADE
);
CREATE TABLE tickets (
    ticket_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticketname VARCHAR(255) NOT NULL,
    price INT(11) NOT NULL,
    location VARCHAR(255) NOT NULL,
    content VARCHAR(255),
    rating DECIMAL(2,1) DEFAULT 0.0,
    number_rating INT(11) DEFAULT 0,
    img_url VARCHAR(255),
    img_detail1 VARCHAR(255),
    img_detail2 VARCHAR(255),
    img_detail3 VARCHAR(255),
    ticket_describe TEXT,
    itinerary TEXT
);

insert users( usr_name ,
    email,
    password ,
    role) values('admin','admin@aloha.com','admin18','admin');
-- Bảng Hotels (Khách sạn)
create TABLE Hotels (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    destination_id INT NOT NULL,
    description TEXT NULL,
    rating DECIMAL(2,1) DEFAULT 0.0,
     img_url varchar(255),
	 img_detail1 VARCHAR(255),
	 img_detail2 VARCHAR(255),
	img_detail3 VARCHAR(255),
    FOREIGN KEY (destination_id) REFERENCES Destinations(destination_id) ON DELETE CASCADE
);
create TABLE Rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_type VARCHAR(255) NOT NULL,
    price_per_night INT NOT NULL,
    max_guests INT NOT NULL,
    stock INT NOT NULL DEFAULT 4, 
    img_url VARCHAR(255),
    FOREIGN KEY (hotel_id) REFERENCES Hotels(hotel_id) ON DELETE CASCADE
);

create TABLE hotel_bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    hotel_id int,
	usr_id INT NOT NULL,
    check_in DATE,
    check_out DATE,
    quantity INT DEFAULT 1,
    total_price int NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (usr_id) REFERENCES Users(usr_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE,
       FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE
);
create TABLE tour_bookings (
booking_id INT PRIMARY KEY AUTO_INCREMENT,
  usr_id INT NOT NULL,
  tour_id INT NOT NULL,
  tour_date_id INT NOT NULL,
  num_people INT NOT NULL,
  total_price int NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  booking_code VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
  FOREIGN KEY (usr_id) REFERENCES users(usr_id) ON DELETE CASCADE,
  FOREIGN KEY (tour_date_id) REFERENCES tour_dates(tour_date_id) ON DELETE CASCADE
);
create TABLE Ticket_Bookings (
	booking_id INT AUTO_INCREMENT PRIMARY KEY,
    usr_id INT NOT NULL,
	ticket_id int not null,
	total_price int NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','confirmed' ,'paid', 'canceled') DEFAULT 'pending',
    FOREIGN KEY (usr_id) REFERENCES Users(usr_id) ON DELETE CASCADE,
	FOREIGN KEY (ticket_id) REFERENCES Tickets(ticket_id) ON DELETE CASCADE
);
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    usr_id INT,	
    booking_type ENUM('Hotel', 'Tour', 'Ticket', 'Guide'),
    booking_item_id INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usr_id) REFERENCES users(usr_id) ON DELETE CASCADE
);

INSERT INTO Destinations (name, description, image, location) VALUES
('An Giang', 'Vùng đất tâm linh nổi tiếng với Núi Cấm, Miếu Bà Chúa Xứ.', '../img/an_giang.jpg', 'Miền Tây Nam Bộ, Việt Nam'),
('Cà Mau', 'Điểm cực Nam của Việt Nam, nổi tiếng với rừng U Minh và Đất Mũi.', '../img/ca_mau.jpg', 'Miền Tây Nam Bộ, Việt Nam'),
('Bạc Liêu', 'Quê hương Công tử Bạc Liêu, nổi tiếng với giai thoại giàu sang.', '../img/bac_lieu.jpg', 'Miền Tây Nam Bộ, Việt Nam'),
('Cần Thơ', 'Thành phố trung tâm miền Tây, nổi bật với chợ nổi Cái Răng.', '../img/can_tho.jpg', 'Miền Tây Nam Bộ, Việt Nam'),
('Sóc Trăng', 'Vùng đất đa văn hóa, nổi tiếng với chùa Dơi và ẩm thực Khmer.', '../img/soc_trang.jpg', 'Miền Tây Nam Bộ, Việt Nam'),
('Tiền Giang', 'Nơi có chợ nổi Cái Bè, cù lao Thới Sơn và vườn trái cây.', '../img/tien_giang.jpg', 'Miền Tây Nam Bộ, Việt Nam');

DELIMITER //
drop TRIGGER after_booking_insert
AFTER INSERT ON hotel_bookings
FOR EACH ROW
BEGIN
    UPDATE rooms
    SET stock = stock - NEW.quantity
    WHERE room_id = NEW.room_id AND stock >= NEW.quantity;
END;

DELIMITER $$
create TRIGGER check_stock_before_booking
BEFORE INSERT ON hotel_bookings
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;
    SELECT stock INTO current_stock FROM rooms WHERE room_id = NEW.room_id;
    
    IF (current_stock - NEW.quantity < 0) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Vượt quá số phòng có sẵn';
    END IF;
END$$
DELIMITER ;



DELIMITER //

create TRIGGER after_hotel_booking_insert
AFTER INSERT ON hotel_bookings
FOR EACH ROW
BEGIN
    UPDATE Rooms
    SET stock = stock - NEW.quantity
    WHERE room_id = NEW.room_id;
END;

//


CREATE TABLE tour_dates (
    tour_date_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    departure_date DATE NOT NULL,
    end_date date not null,
    max_slots INT NOT NULL,
    available_slots INT NOT NULL,
    status ENUM('available', 'full', 'expired') DEFAULT 'available',
    FOREIGN KEY (tour_id) REFERENCES Tours(tour_id) on delete cascade
);

DELIMITER //

-- Trigger cho hotel_bookings
CREATE TRIGGER restockroom_afterdelete
AFTER DELETE ON hotel_bookings
FOR EACH ROW
BEGIN
    UPDATE Rooms
    SET stock = stock + OLD.quantity
    WHERE room_id = OLD.room_id;
END //

-- Trigger cho tour_bookings
CREATE TRIGGER restock_slottour_afterdelete
AFTER DELETE ON tour_bookings
FOR EACH ROW
BEGIN
    UPDATE tour_dates
    SET available_slots = available_slots + OLD.num_people
    WHERE tour_date_id = OLD.tour_date_id;
    UPDATE tour_dates
    SET status = 'available'
    WHERE tour_date_id = OLD.tour_date_id 
    AND available_slots > 0;
END //
use mktrip;
SELECT 'guide' as type, booking_id, usr_id, booking_date, total_price, status FROM guide_bookings
        UNION ALL
SELECT 'hotel' as type, booking_id, usr_id, created_at as booking_date, total_price, status FROM hotel_bookings
        UNION ALL
SELECT 'tour' as type,  tb.booking_id, tb.usr_id, tb.created_at as booking_date,tb.total_price, tb.status
from tour_bookings tb   
inner join tour_dates td on tb.tour_id=td.tour_id
        UNION ALL
SELECT 'ticket' as type, booking_id, usr_id, booking_date, total_price, status FROM ticket_bookings
        ORDER BY booking_date DESC
        LIMIT 4


select *
from tours;
