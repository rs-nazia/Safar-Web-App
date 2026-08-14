import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function AgencyDashboard({ user }) {
  const navigate = useNavigate();
  const [bookings, setBookings] = useState([]);
  const [packages, setPackages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAddForm, setShowAddForm] = useState(false);

  // Form State for creating packages
  const [title, setTitle] = useState('');
  const [location, setLocation] = useState('');
  const [price, setPrice] = useState('');
  const [description, setDescription] = useState('');
  const [imageUrl, setImageUrl] = useState('');
  const [type, setType] = useState('tour');
  
  const [formError, setFormError] = useState('');
  const [formSuccess, setFormSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const fetchData = () => {
    setLoading(true);
    // Fetch bookings & packages
    Promise.all([
      axios.get('/api/bookings'),
      axios.get('/api/packages')
    ])
    .then(([bookingsRes, packagesRes]) => {
      setBookings(bookingsRes.data);
      // Filter packages to only those owned by this agency
      // In production, the backend returns filtered packages for the logged in agency,
      // but we filter locally if needed. We'll show all packages posted by this agency.
      setPackages(packagesRes.data.filter(p => p.company_name === user?.name || user?.role === 'admin'));
      setLoading(false);
    })
    .catch(err => {
      console.error(err);
      setLoading(false);
    });
  };

  useEffect(() => {
    if (!user || user.role !== 'agency') {
      navigate('/login');
      return;
    }
    fetchData();
  }, [user]);

  const handleCreatePackage = (e) => {
    e.preventDefault();
    setFormError('');
    setFormSuccess('');
    setSubmitting(true);

    axios.post('/api/packages', {
      title,
      location,
      price: Number(price),
      description,
      image_url: imageUrl,
      type
    })
    .then(() => {
      setSubmitting(false);
      setFormSuccess('Package created successfully!');
      setTitle('');
      setLocation('');
      setPrice('');
      setDescription('');
      setImageUrl('');
      fetchData(); // Refresh data
      setTimeout(() => setShowAddForm(false), 1500);
    })
    .catch(err => {
      setSubmitting(false);
      setFormError(err.response?.data?.detail || 'Failed to create package.');
    });
  };

  const handleBookingAction = (bookingId, action) => {
    axios.put(`/api/bookings/${bookingId}/status`, { status: action })
      .then(() => {
        fetchData(); // Refresh list
      })
      .catch(err => {
        alert(err.response?.data?.detail || 'Failed to complete booking action.');
      });
  };

  if (loading) {
    return <div style={{ textAlign: 'center', marginTop: '100px' }}><h3>Loading Dashboard...</h3></div>;
  }

  return (
    <div className="container" style={{ margin: '40px auto' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
        <h2>Agency Dashboard ({user?.name})</h2>
        <button className="btn" onClick={() => setShowAddForm(!showAddForm)}>
          {showAddForm ? 'View Listings & Bookings' : '+ Create Package / Hotel'}
        </button>
      </div>

      {showAddForm ? (
        <div style={{ maxWidth: '600px', margin: '0 auto', background: 'white', padding: '40px', borderRadius: '12px', boxShadow: 'var(--shadow-md)' }}>
          <h3 style={{ marginBottom: '20px' }}>Create New Listing</h3>
          {formError && <div className="alert alert-danger">{formError}</div>}
          {formSuccess && <div className="alert alert-success">{formSuccess}</div>}

          <form onSubmit={handleCreatePackage}>
            <div className="form-group">
              <label>Listing Title</label>
              <input type="text" className="form-control" required value={title} onChange={e => setTitle(e.target.value)} />
            </div>
            <div className="form-group">
              <label>Location</label>
              <input type="text" className="form-control" required placeholder="e.g. Kyoto, Japan" value={location} onChange={e => setLocation(e.target.value)} />
            </div>
            <div className="form-group">
              <label>Price</label>
              <input type="number" className="form-control" required min="1" value={price} onChange={e => setPrice(e.target.value)} />
            </div>
            <div className="form-group">
              <label>Type</label>
              <select className="form-control" value={type} onChange={e => setType(e.target.value)}>
                <option value="tour">Tour Package</option>
                <option value="hotel">Hotel Accommodation</option>
              </select>
            </div>
            <div className="form-group">
              <label>Image URL</label>
              <input type="url" className="form-control" placeholder="https://unsplash.com/..." value={imageUrl} onChange={e => setImageUrl(e.target.value)} />
            </div>
            <div className="form-group">
              <label>Description</label>
              <textarea className="form-control" rows="5" required value={description} onChange={e => setDescription(e.target.value)}></textarea>
            </div>

            <button type="submit" className="btn" style={{ width: '100%' }} disabled={submitting}>
              {submitting ? 'Creating...' : 'Create Package'}
            </button>
          </form>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '40px' }}>
          {/* Agency Listings Section */}
          <div style={{ background: 'white', padding: '30px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
            <h3 style={{ marginBottom: '20px' }}>My Active Listings ({packages.length})</h3>
            {packages.length === 0 ? (
              <p style={{ color: 'var(--text-muted)' }}>You don't have any active packages yet.</p>
            ) : (
              <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                  <thead>
                    <tr style={{ borderBottom: '2px solid #e2e8f0', color: 'var(--text-muted)' }}>
                      <th style={{ padding: '15px' }}>Title</th>
                      <th style={{ padding: '15px' }}>Location</th>
                      <th style={{ padding: '15px' }}>Type</th>
                      <th style={{ padding: '15px' }}>Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    {packages.map(p => (
                      <tr key={p.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                        <td style={{ padding: '15px', fontWeight: 600 }}>{p.title}</td>
                        <td style={{ padding: '15px' }}>{p.location}</td>
                        <td style={{ padding: '15px', textTransform: 'capitalize' }}>{p.type}</td>
                        <td style={{ padding: '15px', fontWeight: 700, color: 'var(--primary)' }}>${p.price}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          {/* Incoming Bookings Section */}
          <div style={{ background: 'white', padding: '30px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
            <h3 style={{ marginBottom: '20px' }}>Incoming Booking Requests ({bookings.length})</h3>
            {bookings.length === 0 ? (
              <p style={{ color: 'var(--text-muted)' }}>No booking requests received yet.</p>
            ) : (
              <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                  <thead>
                    <tr style={{ borderBottom: '2px solid #e2e8f0', color: 'var(--text-muted)' }}>
                      <th style={{ padding: '15px' }}>Traveler</th>
                      <th style={{ padding: '15px' }}>Package</th>
                      <th style={{ padding: '15px' }}>Reserved Date</th>
                      <th style={{ padding: '15px' }}>Status</th>
                      <th style={{ padding: '15px' }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {bookings.map(b => (
                      <tr key={b.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                        <td style={{ padding: '15px' }}>
                          <div><strong>{b.traveler_name}</strong></div>
                          <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{b.traveler_email}</span>
                        </td>
                        <td style={{ padding: '15px', fontWeight: 600 }}>{b.package_title}</td>
                        <td style={{ padding: '15px' }}>{new Date(b.booking_date).toLocaleDateString()}</td>
                        <td style={{ padding: '15px' }}>
                          <span className={`badge badge-${b.status}`}>{b.status}</span>
                        </td>
                        <td style={{ padding: '15px', display: 'flex', gap: '10px' }}>
                          {b.status === 'pending' && (
                            <>
                              <button className="btn btn-accent" onClick={() => handleBookingAction(b.id, 'approved')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                                Approve
                              </button>
                              <button className="btn btn-danger" onClick={() => handleBookingAction(b.id, 'rejected')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                                Reject
                              </button>
                            </>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
