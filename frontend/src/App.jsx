import React, { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Link, useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';

// Page Imports (will be created in separate files)
import Home from './pages/Home.jsx';
import Explore from './pages/Explore.jsx';
import Details from './pages/Details.jsx';
import Login from './pages/Login.jsx';
import Signup from './pages/Signup.jsx';
import Profile from './pages/Profile.jsx';
import TravelerDashboard from './pages/dashboards/TravelerDashboard.jsx';
import AgencyDashboard from './pages/dashboards/AgencyDashboard.jsx';
import AdminDashboard from './pages/dashboards/AdminDashboard.jsx';

export default function App() {
  const [token, setToken] = useState(localStorage.getItem('token') || '');
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Configure Axios defaults
  useEffect(() => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem('token', token);
      
      // Fetch user profile
      axios.get('/api/auth/profile')
        .then(res => {
          setUser(res.data);
          setLoading(false);
        })
        .catch(() => {
          // Token expired or invalid
          handleLogout();
          setLoading(false);
        });
    } else {
      delete axios.defaults.headers.common['Authorization'];
      localStorage.removeItem('token');
      setUser(null);
      setLoading(false);
    }
  }, [token]);

  const handleLogout = () => {
    setToken('');
    setUser(null);
    localStorage.removeItem('token');
  };

  if (loading) {
    return <div style={{ textAlign: 'center', marginTop: '100px' }}><h3>Loading SAFAR...</h3></div>;
  }

  return (
    <BrowserRouter>
      <Layout user={user} handleLogout={handleLogout}>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/explore" element={<Explore user={user} />} />
          <Route path="/packages/:id" element={<Details user={user} />} />
          <Route path="/login" element={<Login setToken={setToken} />} />
          <Route path="/signup" element={<Signup />} />
          <Route path="/profile" element={<Profile user={user} setUser={setUser} />} />
          <Route path="/dashboard/traveler" element={<TravelerDashboard user={user} />} />
          <Route path="/dashboard/agency" element={<AgencyDashboard user={user} />} />
          <Route path="/admin" element={<AdminDashboard user={user} />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Layout>
    </BrowserRouter>
  );
}

// Layout wrapper including Header and Footer
function Layout({ children, user, handleLogout }) {
  const location = useLocation();
  const navigate = useNavigate();

  // Scroll to top on route change
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [location.pathname]);

  return (
    <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      {/* Navbar Component */}
      <nav className="navbar glass" style={{ position: 'fixed', top: 0, left: 0, right: 0, zIndex: 1000, padding: '15px 0' }}>
        <div className="container" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Link to="/" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span style={{ fontSize: '1.8rem', fontWeight: 800, color: 'var(--primary)', letterSpacing: '-0.5px' }}>SAFAR</span>
          </Link>
          
          <ul style={{ display: 'flex', gap: '30px', alignItems: 'center', listStyle: 'none' }}>
            <li><Link to="/explore" style={{ fontWeight: 600, color: 'var(--text-main)' }}>Explore</Link></li>
            <li><Link to="/explore?type=tour" style={{ fontWeight: 600, color: 'var(--text-main)' }}>Tours</Link></li>
            <li><Link to="/explore?type=hotel" style={{ fontWeight: 600, color: 'var(--text-main)' }}>Hotels</Link></li>
            
            {user ? (
              <>
                {user.role === 'traveler' && (
                  <li><Link to="/dashboard/traveler" style={{ fontWeight: 600, color: 'var(--text-main)' }}>My Bookings</Link></li>
                )}
                {user.role === 'agency' && (
                  <li><Link to="/dashboard/agency" style={{ fontWeight: 600, color: 'var(--text-main)' }}>Agency Dashboard</Link></li>
                )}
                {user.role === 'admin' && (
                  <li><Link to="/admin" style={{ fontWeight: 600, color: 'var(--text-main)' }}>Admin Panel</Link></li>
                )}
                <li>
                  <Link to="/profile" style={{ 
                    padding: '8px 20px', 
                    borderRadius: '8px', 
                    fontWeight: 600, 
                    background: 'var(--primary)', 
                    color: 'var(--white)' 
                  }}>Profile</Link>
                </li>
                <li>
                  <button onClick={() => { handleLogout(); navigate('/'); }} style={{ 
                    padding: '8px 20px', 
                    borderRadius: '8px', 
                    fontWeight: 600, 
                    border: '2px solid var(--primary)', 
                    color: 'var(--primary)',
                    background: 'transparent',
                    cursor: 'pointer'
                  }}>Logout</button>
                </li>
              </>
            ) : (
              <>
                <li>
                  <Link to="/login" style={{ 
                    padding: '8px 20px', 
                    borderRadius: '8px', 
                    fontWeight: 600, 
                    border: '2px solid var(--primary)', 
                    color: 'var(--primary)',
                    background: 'transparent'
                  }}>Log In</Link>
                </li>
                <li>
                  <Link to="/signup" style={{ 
                    padding: '8px 20px', 
                    borderRadius: '8px', 
                    fontWeight: 600, 
                    background: 'var(--primary)', 
                    color: 'var(--white)'
                  }}>Sign Up</Link>
                </li>
              </>
            )}
          </ul>
        </div>
      </nav>

      {/* Main content body */}
      <main className="main-content" style={{ flex: 1, paddingTop: '110px' }}>
        {children}
      </main>

      {/* Footer Component */}
      <footer style={{ background: '#212121', color: '#fff', padding: '40px 0', marginTop: '60px' }}>
        <div className="container" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '30px' }}>
          <div>
            <h3 style={{ color: 'var(--primary)', marginBottom: '15px' }}>SAFAR</h3>
            <p style={{ color: '#ccc', fontSize: '0.9rem' }}>Your premium travel marketplace. Discover, compare, and book the best tour packages globally.</p>
          </div>
          <div>
            <h4 style={{ marginBottom: '15px' }}>Quick Links</h4>
            <ul style={{ listStyle: 'none', padding: 0 }}>
              <li style={{ marginBottom: '8px' }}><Link to="/" style={{ color: '#ccc' }}>Home</Link></li>
              <li style={{ marginBottom: '8px' }}><Link to="/login" style={{ color: '#ccc' }}>Login</Link></li>
              <li style={{ marginBottom: '8px' }}><Link to="/signup" style={{ color: '#ccc' }}>Register</Link></li>
            </ul>
          </div>
          <div>
            <h4 style={{ marginBottom: '15px' }}>Contact Us</h4>
            <p style={{ color: '#ccc', fontSize: '0.9rem', marginBottom: '8px' }}>Email: support@safar.com</p>
            <p style={{ color: '#ccc', fontSize: '0.9rem' }}>Phone: +1 234 567 890</p>
          </div>
        </div>
        <div style={{ textAlign: 'center', borderTop: '1px solid #333', marginTop: '30px', paddingTop: '20px', color: '#777', fontSize: '0.8rem' }}>
          <p>&copy; {new Date().getFullYear()} SAFAR Travel Booking. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}

// Simple 404 Component
function NotFound() {
  return (
    <div className="container" style={{ textAlign: 'center', padding: '100px 20px' }}>
      <h1 style={{ fontSize: '6rem', color: 'var(--primary)', marginBottom: '20px' }}>404</h1>
      <h2>Page Not Found</h2>
      <p style={{ color: 'var(--text-muted)', maxWidth: '500px', margin: '20px auto 40px' }}>
        Oops! The page you are looking for doesn't exist or has been moved. Let's get you back on track.
      </p>
      <Link to="/" className="btn">Return to Home</Link>
    </div>
  );
}
