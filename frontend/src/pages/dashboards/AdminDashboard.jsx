import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function AdminDashboard({ user }) {
  const navigate = useNavigate();
  const [agencies, setAgencies] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchAgencies = () => {
    setLoading(true);
    axios.get('/api/agencies')
      .then(res => {
        setAgencies(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  };

  useEffect(() => {
    if (!user || user.role !== 'admin') {
      navigate('/login');
      return;
    }
    fetchAgencies();
  }, [user]);

  const handleStatusChange = (agencyId, action) => {
    axios.put(`/api/agencies/${agencyId}/status`, { status: action })
      .then(() => {
        fetchAgencies(); // Refresh table list
      })
      .catch(err => {
        alert(err.response?.data?.detail || 'Failed to update agency status.');
      });
  };

  if (loading) {
    return <div style={{ textAlign: 'center', marginTop: '100px' }}><h3>Loading Admin Panel...</h3></div>;
  }

  const pendingAgencies = agencies.filter(a => a.status === 'pending');
  const verifiedAgencies = agencies.filter(a => a.status === 'verified');

  return (
    <div className="container" style={{ margin: '40px auto' }}>
      <h2 style={{ marginBottom: '30px' }}>Admin Panel - Agency Credentials Verification</h2>

      {/* Metric Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '20px', marginBottom: '40px' }}>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Total Registered Agencies</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: 'var(--primary)' }}>{agencies.length}</span>
        </div>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Pending Verification</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: '#d97706' }}>{pendingAgencies.length}</span>
        </div>
        <div style={{ background: 'white', padding: '25px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
          <h4 style={{ color: 'var(--text-muted)', marginBottom: '10px' }}>Verified Partners</h4>
          <span style={{ fontSize: '2.5rem', fontWeight: 800, color: '#059669' }}>{verifiedAgencies.length}</span>
        </div>
      </div>

      {/* Verification Manager Table */}
      <div style={{ background: 'white', padding: '30px', borderRadius: '12px', border: '1px solid var(--glass-border)', boxShadow: 'var(--shadow-sm)' }}>
        <h3 style={{ marginBottom: '20px' }}>Agency Registrations Manager</h3>
        {agencies.length === 0 ? (
          <p style={{ color: 'var(--text-muted)' }}>No travel agencies registered on SAFAR yet.</p>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
              <thead>
                <tr style={{ borderBottom: '2px solid #e2e8f0', color: 'var(--text-muted)' }}>
                  <th style={{ padding: '15px' }}>Agency ID</th>
                  <th style={{ padding: '15px' }}>Company Name</th>
                  <th style={{ padding: '15px' }}>Phone Number</th>
                  <th style={{ padding: '15px' }}>Verification Status</th>
                  <th style={{ padding: '15px' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {agencies.map(a => (
                  <tr key={a.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                    <td style={{ padding: '15px' }}>#{a.id}</td>
                    <td style={{ padding: '15px', fontWeight: 600 }}>{a.company_name}</td>
                    <td style={{ padding: '15px' }}>{a.phone || 'N/A'}</td>
                    <td style={{ padding: '15px' }}>
                      <span className={`badge badge-${a.status}`}>{a.status}</span>
                    </td>
                    <td style={{ padding: '15px', display: 'flex', gap: '10px' }}>
                      {a.status === 'pending' && (
                        <>
                          <button className="btn btn-accent" onClick={() => handleStatusChange(a.id, 'verified')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                            Verify Partner
                          </button>
                          <button className="btn btn-danger" onClick={() => handleStatusChange(a.id, 'rejected')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                            Reject Request
                          </button>
                        </>
                      )}
                      {a.status === 'verified' && (
                        <button className="btn btn-danger" onClick={() => handleStatusChange(a.id, 'rejected')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                          Revoke Verification
                        </button>
                      )}
                      {a.status === 'rejected' && (
                        <button className="btn btn-accent" onClick={() => handleStatusChange(a.id, 'verified')} style={{ padding: '6px 15px', fontSize: '0.85rem' }}>
                          Re-Verify Partner
                        </button>
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
  );
}
