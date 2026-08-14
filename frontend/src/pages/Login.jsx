import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Login({ setToken }) {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    axios.post('/api/auth/login', { email, password })
      .then(res => {
        setToken(res.data.access_token);
        navigate('/');
      })
      .catch(err => {
        setLoading(false);
        setError(err.response?.data?.detail || 'Invalid email or password.');
      });
  };

  return (
    <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 'calc(100vh - 220px)', padding: '40px 20px' }}>
      <div style={{
        width: '100%',
        maxWidth: '450px',
        background: 'white',
        boxShadow: 'var(--shadow-lg)',
        padding: '50px 40px',
        borderRadius: '12px',
        borderTop: '5px solid var(--primary)'
      }}>
        <h2 style={{ color: 'var(--primary)', textAlign: 'center', marginBottom: '30px', fontWeight: 800 }}>Log In to SAFAR</h2>
        
        {error && <div className="alert alert-danger">{error}</div>}

        <form onSubmit={handleSubmit}>
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
            <label>Password</label>
            <input 
              type="password" 
              className="form-control" 
              required 
              value={password}
              onChange={e => setPassword(e.target.value)}
            />
          </div>
          <button type="submit" className="btn" style={{ width: '100%', marginTop: '10px' }} disabled={loading}>
            {loading ? 'Logging In...' : 'Log In'}
          </button>
        </form>
        <p style={{ textAlign: 'center', marginTop: '20px', fontSize: '0.9rem' }}>
          Don't have an account? <Link to="/signup" style={{ color: 'var(--primary)', fontWeight: 600 }}>Sign up here</Link>
        </p>
      </div>
    </div>
  );
}
