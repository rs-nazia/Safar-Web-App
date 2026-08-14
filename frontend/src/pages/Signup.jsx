import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Signup() {
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('traveler');
  const [companyName, setCompanyName] = useState('');
  const [phone, setPhone] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    const payload = {
      name,
      email,
      password,
      role
    };

    if (role === 'agency') {
      payload.company_name = companyName;
      payload.phone = phone;
    }

    axios.post('/api/auth/register', payload)
      .then(() => {
        setLoading(false);
        setSuccess('Registration successful! You can now log in.');
        setName('');
        setEmail('');
        setPassword('');
        setCompanyName('');
        setPhone('');
      })
      .catch(err => {
        setLoading(false);
        setError(err.response?.data?.detail || 'An error occurred during registration.');
      });
  };

  return (
    <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 'calc(100vh - 220px)', padding: '40px 20px' }}>
      <div style={{
        width: '100%',
        maxWidth: '500px',
        background: 'white',
        boxShadow: 'var(--shadow-lg)',
        padding: '50px 40px',
        borderRadius: '12px',
        borderTop: '5px solid var(--primary)'
      }}>
        <h2 style={{ color: 'var(--primary)', textAlign: 'center', marginBottom: '30px', fontWeight: 800 }}>Create an Account</h2>
        
        {error && <div className="alert alert-danger">{error}</div>}
        {success && <div className="alert alert-success">{success} <Link to="/login" style={{ textDecoration: 'underline' }}>Log In</Link></div>}

        {!success && (
          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label>Full Name</label>
              <input 
                type="text" 
                className="form-control" 
                required 
                value={name}
                onChange={e => setName(e.target.value)}
              />
            </div>
            
            <div className="form-group">
              <label>Email Address</label>
              <input 
                type="email" 
                className="form-control" 
                required 
                value={email}
                onChange={e => setEmail(e.target.value)}
              />
            </div>

            <div className="form-group">
              <label>Account Type</label>
              <select className="form-control" value={role} onChange={e => setRole(e.target.value)}>
                <option value="traveler">Traveler / Customer</option>
                <option value="agency">Travel Agency / Host</option>
              </select>
            </div>

            {role === 'agency' && (
              <>
                <div className="form-group">
                  <label>Company / Agency Name</label>
                  <input 
                    type="text" 
                    className="form-control" 
                    required 
                    value={companyName}
                    onChange={e => setCompanyName(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label>Phone Number</label>
                  <input 
                    type="text" 
                    className="form-control" 
                    required 
                    value={phone}
                    onChange={e => setPhone(e.target.value)}
                  />
                </div>
              </>
            )}

            <div className="form-group">
              <label>Password</label>
              <input 
                type="password" 
                className="form-control" 
                required 
                minLength="6"
                value={password}
                onChange={e => setPassword(e.target.value)}
              />
            </div>

            <button type="submit" className="btn" style={{ width: '100%', marginTop: '10px' }} disabled={loading}>
              {loading ? 'Creating Account...' : 'Create Account'}
            </button>
          </form>
        )}

        <p style={{ textAlign: 'center', marginTop: '20px', fontSize: '0.9rem' }}>
          Already have an account? <Link to="/login" style={{ color: 'var(--primary)', fontWeight: 600 }}>Log in here</Link>
        </p>
      </div>
    </div>
  );
}
