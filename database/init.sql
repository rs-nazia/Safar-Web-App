-- PostgreSQL Database Initialization Script for SAFAR

-- Drop existing tables if they exist (for easy resets)
DROP TABLE IF EXISTS bookings CASCADE;
DROP TABLE IF EXISTS packages CASCADE;
DROP TABLE IF EXISTS agencies CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Create Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'traveler' CHECK (role IN ('traveler', 'agency', 'admin')),
    profile_image VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create Agencies Table
CREATE TABLE agencies (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    company_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'verified', 'rejected'))
);

-- Create Packages Table (Tours & Hotels)
CREATE TABLE packages (
    id SERIAL PRIMARY KEY,
    agency_id INT NOT NULL REFERENCES agencies(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    location VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255),
    type VARCHAR(20) DEFAULT 'tour' CHECK (type IN ('tour', 'hotel')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create Bookings Table
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    traveler_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    package_id INT NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    booking_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- Seed Data Initialization
-- =========================================================================

-- Insert Default Users (Admin, Agency User, Traveler User)
-- Password for all is 'admin123' (bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
-- We also insert standard pbkdf2/bcrypt compatible hashes for python
-- Hash: '$2b$12$6t3M9P0Rz413f1Hk2pLzGOmO.fJ5NpxeZq00pQ7Qp31P1Q.mB5KjK' (which translates to 'admin123' in python bcrypt)
INSERT INTO users (id, name, email, password, role) VALUES 
(1, 'System Admin', 'admin@safar.com', '$2b$12$6t3M9P0Rz413f1Hk2pLzGOmO.fJ5NpxeZq00pQ7Qp31P1Q.mB5KjK', 'admin'),
(2, 'Global Travels Ltd', 'agency@safar.com', '$2b$12$6t3M9P0Rz413f1Hk2pLzGOmO.fJ5NpxeZq00pQ7Qp31P1Q.mB5KjK', 'agency'),
(3, 'John Doe', 'traveler@safar.com', '$2b$12$6t3M9P0Rz413f1Hk2pLzGOmO.fJ5NpxeZq00pQ7Qp31P1Q.mB5KjK', 'traveler');

-- Insert Seed Agency Details (Pre-Verified so they can post packages)
INSERT INTO agencies (id, user_id, company_name, phone, status) VALUES
(1, 2, 'Global Travels Ltd', '+1 555-0199', 'verified');

-- Adjust serial sequences to match manually inserted IDs
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('agencies_id_seq', (SELECT MAX(id) FROM agencies));

-- Insert 10 Seed Tours
INSERT INTO packages (agency_id, title, location, price, description, image_url, type) VALUES
(1, 'Bali Tropical Paradise', 'Bali, Indonesia', 850.00, 'Experience the ultimate tropical getaway with pristine beaches and vibrant culture.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4', 'tour'),
(1, 'Swiss Alps Adventure', 'Zermatt, Switzerland', 1200.00, 'A breathtaking journey through the snowy peaks and charming villages of the Alps.', 'https://images.unsplash.com/photo-1530122037265-a5f1f91d3b99', 'tour'),
(1, 'Kyoto Historical Tour', 'Kyoto, Japan', 950.00, 'Immerse yourself in ancient traditions, stunning temples, and beautiful gardens.', 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e', 'tour'),
(1, 'Sahara Desert Safari', 'Merzouga, Morocco', 600.00, 'Ride camels across golden dunes and camp under the starry desert sky.', 'https://images.unsplash.com/photo-1547463765-b51f153ee61f', 'tour'),
(1, 'Amazon Rainforest Expedition', 'Manaus, Brazil', 1100.00, 'Explore the world''s largest rainforest and its incredible biodiversity.', 'https://images.unsplash.com/photo-1518182170546-076616fdcbfe', 'tour'),
(1, 'Santorini Island Hopping', 'Santorini, Greece', 1400.00, 'Cruise the Aegean Sea and witness the most beautiful sunsets in the world.', 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800', 'tour'),
(1, 'Machu Picchu Trek', 'Cusco, Peru', 890.00, 'Hike the historic Inca Trail to the legendary lost city of the Incas.', 'https://images.unsplash.com/photo-1526392060635-9d6019884377', 'tour'),
(1, 'Northern Lights Safari', 'Tromsø, Norway', 1600.00, 'Chase the magical Aurora Borealis across the snowy Arctic landscapes.', 'https://images.unsplash.com/photo-1531366936336-d63068cecae1', 'tour'),
(1, 'Grand Canyon Rafting', 'Arizona, USA', 750.00, 'An exhilarating white-water rafting experience through the iconic canyon.', 'https://images.unsplash.com/photo-1474044159687-1ee9f3a51722', 'tour'),
(1, 'Taj Mahal & Golden Triangle', 'Agra, India', 680.00, 'A majestic tour of India''s most iconic cultural and historical landmarks.', 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da', 'tour');

-- Insert 10 Seed Hotels
INSERT INTO packages (agency_id, title, location, price, description, image_url, type) VALUES
(1, 'The Plaza Hotel', 'New York City, USA', 450.00, 'Luxury 5-star hotel offering iconic views of Central Park and world-class service.', 'https://images.unsplash.com/photo-1566073771259-6a8506099945', 'hotel'),
(1, 'Burj Al Arab', 'Dubai, UAE', 1200.00, 'Experience unparalleled luxury in the world''s only 7-star hotel structure.', 'https://images.unsplash.com/photo-1582719508461-905c673771fd', 'hotel'),
(1, 'Marina Bay Sands', 'Singapore', 600.00, 'Iconic integrated resort featuring the world''s largest rooftop Infinity Pool.', 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4', 'hotel'),
(1, 'Ritz Paris', 'Paris, France', 850.00, 'Classic elegance and sophisticated Parisian charm in the heart of the city.', 'https://images.unsplash.com/photo-1551882547-ff40c0d51c12', 'hotel'),
(1, 'Atlantis The Palm', 'Dubai, UAE', 550.00, 'Ocean-themed destination resort offering thrilling waterparks and marine habitats.', 'https://images.unsplash.com/photo-1580828343064-fde4cad202d5', 'hotel'),
(1, 'Aman Tokyo', 'Tokyo, Japan', 900.00, 'A serene sanctuary high above the vibrant city, blending traditional and modern design.', 'https://images.unsplash.com/photo-1542314831-c6a4d140b2ee', 'hotel'),
(1, 'The Savoy', 'London, UK', 500.00, 'Historic luxury hotel on the River Thames, redefining elegance for over a century.', 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6', 'hotel'),
(1, 'Four Seasons Bora Bora', 'Bora Bora, French Polynesia', 1500.00, 'Overwater bungalows and pristine lagoons for the ultimate romantic escape.', 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2', 'hotel'),
(1, 'Waldorf Astoria', 'Maldives', 1800.00, 'Exclusive private island resort offering bespoke luxury and breathtaking ocean views.', 'https://images.unsplash.com/photo-1437719417032-8595fd9e9dc6', 'hotel'),
(1, 'Amangiri Resort', 'Utah, USA', 2000.00, 'A remote luxury retreat seamlessly integrated into the dramatic canyon landscape.', 'https://images.unsplash.com/photo-1517840901100-8179e982acb7', 'hotel');
