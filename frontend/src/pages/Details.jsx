import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Details({ user }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const [pkg, setPkg] = useState(null);
  const [guests, setGuests] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [paymentSuccess, setPaymentSuccess] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  // Fetch package details
  useEffect(() => {
    axios.get(`/api/packages/${id}`)
      .then(res => {
        setPkg(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  }, [id]);

  if (loading) {
    return <div style={{ textAlign: 'center', marginTop: '100px' }}><h3>Loading details...</h3></div>;
  }

  if (!pkg) {
    return (
      <div className="container" style={{ textAlign: 'center', padding: '100px 20px' }}>
        <h2>Package Not Found</h2>
        <Link to="/explore" className="btn" style={{ marginTop: '20px' }}>Return to Explore</Link>
      </div>
    );
  }

  // Calculate pricing based on guest count and package type
  const calculateTotal = () => {
    const base = Number(pkg.price);
    const guestMultiplier = Math.max(1, guests);
    if (pkg.type === 'hotel') {
      const subtotal = base * guestMultiplier;
      const resortFee = subtotal * 0.1; // 10% Daily resort fee strategy
      return (subtotal + resortFee).toFixed(2);
    } else {
      return (base * guestMultiplier).toFixed(2);
    }
  };

  const handleBookingSubmit = (e) => {
    e.preventDefault();
    if (!user) {
      navigate('/login');
      return;
    }
    setShowModal(true);
  };

  const handlePaymentConfirm = () => {
    setProcessing(true);
    setError('');

    // Simulate payment transaction
    setTimeout(() => {
      axios.post(`/api/bookings?guests=${guests}`, { package_id: pkg.id })
        .then(() => {
          setProcessing(false);
          setPaymentSuccess(true);
        })
        .catch(err => {
          setProcessing(false);
          setError(err.response?.data?.detail || 'An error occurred during booking.');
        });
    }, 1500);
  };

  return (
    <div>
      {/* Hero Header */}
      <div style={{
        position: 'relative',
        height: '400px',
        backgroundImage: `url(${pkg.image_url || 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80'})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        boxShadow: 'var(--shadow-sm)',
        marginBottom: '40px'
      }}>
        <div style={{
          position: 'absolute',
          top: 0, left: 0, width: '100%', height: '100%',
          background: 'linear-gradient(to top, rgba(0,0,0,0.8), transparent)'
        }} />
        <div className="container" style={{
          position: 'relative',
          height: '100%',
          display: 'flex',
          alignItems: 'flex-end',
          paddingBottom: '40px',
          zIndex: 2,
          color: 'white'
        }}>
          <div>
            <span className="badge badge-approved" style={{ background: 'var(--primary)', color: 'white', marginBottom: '10px' }}>
              {pkg.type === 'hotel' ? 'Hotel Accommodation' : 'Tour package'}
            </span>
            <h1 style={{ fontSize: '2.5rem', fontWeight: 800 }}>{pkg.title}</h1>
            <p style={{ opacity: 0.9 }}>📍 {pkg.location}</p>
          </div>
        </div>
      </div>

      <div className="container" style={{ display: 'flex', gap: '40px', flexWrap: 'wrap' }}>
        {/* Description Section */}
        <div style={{ flex: '2', minWidth: '300px' }}>
          <h2 style={{ borderBottom: '2px solid var(--primary)', paddingBottom: '10px', marginBottom: '20px' }}>Overview</h2>
          <p style={{ fontSize: '1.1rem', color: '#444', whiteSpace: 'pre-line', marginBottom: '30px' }}>{pkg.description}</p>
          
          <div style={{ background: 'white', padding: '25px', borderRadius: '12px', boxShadow: 'var(--shadow-sm)', border: '1px solid var(--glass-border)' }}>
            <h3>Highlights</h3>
            <ul style={{ paddingLeft: '20px', marginTop: '10px' }}>
              <li style={{ marginBottom: '8px' }}>Verified Local Service Provider: <strong>{pkg.company_name}</strong></li>
              <li style={{ marginBottom: '8px' }}>Secure instant booking processing</li>
              <li style={{ marginBottom: '8px' }}>Strategy-optimized daily guest pricing</li>
            </ul>
          </div>
        </div>

        {/* Sidebar Booking Card */}
        <aside style={{ flex: '1', minWidth: '280px', alignSelf: 'flex-start', position: 'sticky', top: '120px' }}>
          <div style={{
            background: 'white',
            borderRadius: '16px',
            padding: '30px',
            boxShadow: 'var(--shadow-md)',
            border: '1px solid var(--glass-border)'
          }}>
            <h3 style={{ marginBottom: '5px' }}>Price Details</h3>
            <p style={{ fontSize: '1.8rem', fontWeight: 800, color: 'var(--primary)', marginBottom: '20px' }}>
              ${pkg.price} <span style={{ fontSize: '1rem', fontWeight: 400, color: 'var(--text-muted)' }}>/ {pkg.type === 'hotel' ? 'night' : 'person'}</span>
            </p>

            <form onSubmit={handleBookingSubmit}>
              <div className="form-group">
                <label>Number of Guests</label>
                <input 
                  type="number" 
                  min="1" 
                  max="10" 
                  className="form-control" 
                  value={guests} 
                  onChange={e => setGuests(Math.max(1, Number(e.target.value)))}
                />
              </div>

              <div style={{ borderTop: '1px solid #eee', paddingTop: '15px', marginBottom: '20px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                  <span>Base Rate ({guests} {guests === 1 ? 'guest' : 'guests'})</span>
                  <span>${pkg.price * guests}</span>
                </div>
                {pkg.type === 'hotel' && (
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px', color: 'var(--text-muted)', fontSize: '0.9rem' }}>
                    <span>Daily Resort Fee (10%)</span>
                    <span>${(pkg.price * guests * 0.1).toFixed(2)}</span>
                  </div>
                )}
                <div style={{ display: 'flex', justifyContent: 'space-between', fontWeight: 800, fontSize: '1.2rem', marginTop: '10px' }}>
                  <span>Estimated Total</span>
                  <span style={{ color: 'var(--primary)' }}>${calculateTotal()}</span>
                </div>
              </div>

              <button type="submit" className="btn" style={{ width: '100%', fontSize: '1.1rem', padding: '15px' }}>
                {user ? 'Book Now' : 'Login to Book'}
              </button>
            </form>
          </div>
        </aside>
      </div>

      {/* Simulated Payment Gateway Modal */}
      {showModal && (
        <div style={{
          position: 'fixed',
          top: 0, left: 0, width: '100%', height: '100%',
          backgroundColor: 'rgba(0,0,0,0.5)',
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          zIndex: 2000
        }}>
          <div style={{
            background: 'white',
            borderRadius: '16px',
            padding: '40px',
            maxWidth: '500px',
            width: '90%',
            boxShadow: 'var(--shadow-lg)',
            textAlign: 'center'
          }}>
            {!paymentSuccess ? (
              <>
                <h2 style={{ marginBottom: '10px' }}>Secure Payment Simulation</h2>
                <p style={{ color: 'var(--text-muted)', marginBottom: '25px' }}>You are reserving <strong>{pkg.title}</strong> for <strong>{guests}</strong> guest(s).</p>
                
                <div style={{ border: '1px solid #e2e8f0', borderRadius: '12px', padding: '20px', background: '#f8fafc', marginBottom: '25px', textAlign: 'left' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <span>Recipient:</span>
                    <strong>{pkg.company_name}</strong>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.2rem', fontWeight: 800 }}>
                    <span>Amount Due:</span>
                    <span style={{ color: 'var(--primary)' }}>${calculateTotal()}</span>
                  </div>
                </div>

                {error && <div className="alert alert-danger">{error}</div>}

                <div style={{ display: 'flex', gap: '15px' }}>
                  <button 
                    onClick={() => setShowModal(false)} 
                    className="btn btn-outline" 
                    style={{ flex: 1 }}
                    disabled={processing}
                  >
                    Cancel
                  </button>
                  <button 
                    onClick={handlePaymentConfirm} 
                    className="btn" 
                    style={{ flex: 1 }}
                    disabled={processing}
                  >
                    {processing ? 'Processing...' : `Pay $${calculateTotal()}`}
                  </button>
                </div>
              </>
            ) : (
              <div style={{ padding: '20px 0' }}>
                <div style={{
                  width: '80px', height: '80px',
                  borderRadius: '50%',
                  background: '#10b981',
                  margin: '0 auto 20px',
                  display: 'flex',
                  justifyContent: 'center',
                  alignItems: 'center',
                  color: 'white',
                  fontSize: '2.5rem'
                }}>
                  ✓
                </div>
                <h2>Booking Confirmed!</h2>
                <p style={{ color: 'var(--text-muted)', margin: '15px 0 30px' }}>Your simulated payment was successful. The agency is reviewing your request.</p>
                <button 
                  onClick={() => {
                    setShowModal(false);
                    navigate('/dashboard/traveler');
                  }} 
                  className="btn"
                  style={{ padding: '12px 35px' }}
                >
                  View My Bookings
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
