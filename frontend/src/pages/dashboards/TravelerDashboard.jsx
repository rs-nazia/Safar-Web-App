import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function TravelerDashboard({ user }) {
  const navigate = useNavigate();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  // Fetch traveler bookings
  useEffect(() => {
    if (!user || user.role !== 'traveler') {
      navigate('/login');
      return;
    }

    axios.get('/api/bookings')
      .then(res => {
        setBookings(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  }, [user]);

  if (loading) {
    return <div style={{ textAlign: 'center', marginTop: '100px' }}><h3>Loading Dashboard...</h3></div>;
  }

  // Count booking metrics
  const pendingCount = bookings.filter(b => b.status === 'pending').length;
  const approvedCount = bookings.filter(b => b.status === 'approved').length;

  return (
    <div className="container" style={{ margin: '40px auto' }}>
      <h2 style={{ marginBottom: '30px' }}>Traveler Dashboard</h2>
      
      {/* Metric Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '20px', marginBottom: '40px' }}>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Total Bookings</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: 'var(--primary)' }}>{bookings.length}</span>
        </div>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Pending Approvals</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: '#d97706' }}>{pendingCount}</span>
        </div>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Approved Trips</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: '#059669' }}>{approvedCount}</span>
        </div>
      </div>

      {/* Booking History Table */}
      <div style={{ background: 'white', padding: '30px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
        <h3 style={{ marginBottom: '20px' }}>My Reservation History</h3>
        
        {bookings.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '30px', border: '1px dashed #ccc', borderRadius: '8px' }}>
            <p style={{ color: 'var(--text-muted)' }}>You don't have any reservations yet.</p>
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
              <thead>
                <tr style={{ borderBottom: '2px solid #e2e8f0', color: 'var(--text-muted)' }}>
                  <th style={{ padding: '15px' }}>Trip Name</th>
                  <th style={{ padding: '15px' }}>Type</th>
                  <th style={{ padding: '15px' }}>Rate</th>
                  <th style={{ padding: '15px' }}>Date Reserved</th>
                  <th style={{ padding: '15px' }}>Status</th>
                </tr>
              </thead>
              <tbody>
                {bookings.map(b => (
                  <tr key={b.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                    <td style={{ padding: '15px', fontWeight: 600 }}>{b.package_title}</td>
                    <td style={{ padding: '15px', textTransform: 'capitalize' }}>{b.package_type}</td>
                    <td style={{ padding: '15px', fontWeight: 700, color: 'var(--primary)' }}>${b.package_price}</td>
                    <td style={{ padding: '15px' }}>{new Date(b.booking_date).toLocaleDateString()}</td>
                    <td style={{ padding: '15px' }}>
                      <span className={`badge badge-${b.status}`}>
                        {b.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
