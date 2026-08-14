import React, { useState } from 'react';
import axios from 'axios';

export default function Profile({ user, setUser }) {
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [password, setPassword] = useState('');
  const [avatar, setAvatar] = useState(user?.profile_image || '');
  const [uploadStatus, setUploadStatus] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setUploadStatus('Uploading image...');
    const formData = new FormData();
    formData.append('profile_image', file);

    axios.post('/api/auth/upload-avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    .then(res => {
      setAvatar(res.data.file_path);
      setUploadStatus('Image uploaded successfully. Save changes to update profile.');
    })
    .catch(() => {
      setUploadStatus('Image upload failed.');
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setSuccess('');
    setError('');
    setSaving(true);

    const payload = { name, email };
    if (password) payload.password = password;
    if (avatar) payload.profile_image = avatar;

    axios.put('/api/auth/profile', payload)
      .then(res => {
        setUser(res.data);
        setSaving(false);
        setSuccess('Profile updated successfully!');
        setPassword('');
      })
      .catch(err => {
        setSaving(false);
        setError(err.response?.data?.detail || 'An error occurred while updating profile.');
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
        <h2 style={{ color: 'var(--primary)', textAlign: 'center', marginBottom: '30px', fontWeight: 800 }}>My Profile</h2>
        
        {success && <div className="alert alert-success">{success}</div>}
        {error && <div className="alert alert-danger">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div style={{ textAlign: 'center', marginBottom: '30px' }}>
            <div style={{
              position: 'relative',
              width: '120px',
              height: '120px',
              margin: '0 auto 15px',
              borderRadius: '50%',
              overflow: 'hidden',
              border: '3px solid var(--primary)',
              boxShadow: 'var(--shadow-sm)'
            }}>
              {avatar ? (
                <img 
                  src={'/' + avatar} 
                  alt="Avatar" 
                  style={{ width: '100%', height: '100%', objectFit: 'cover' }} 
                />
              ) : (
                <div style={{
                  width: '100%', height: '100%',
                  background: '#f1f5f9',
                  display: 'flex',
                  justifyContent: 'center',
                  alignItems: 'center',
                  fontSize: '3rem',
                  color: 'var(--primary)'
                }}>
                  👤
                </div>
              )}
            </div>
            
            <label style={{
              display: 'inline-block',
              background: 'var(--bg-light)',
              color: 'var(--primary)',
              border: '1px solid var(--primary)',
              padding: '6px 15px',
              borderRadius: '20px',
              cursor: 'pointer',
              fontWeight: 600,
              fontSize: '0.85rem'
            }}>
              Change Avatar
              <input 
                type="file" 
                accept="image/*" 
                onChange={handleAvatarChange} 
                style={{ display: 'none' }} 
              />
            </label>
            {uploadStatus && <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '8px' }}>{uploadStatus}</p>}
          </div>

          <div className="form-group">
            <label>Name</label>
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
            <label>New Password <span style={{ fontSize: '0.8rem', fontWeight: 400, color: 'var(--text-muted)' }}>(Leave blank to keep current)</span></label>
            <input 
              type="password" 
              className="form-control" 
              placeholder="••••••••"
              value={password}
              onChange={e => setPassword(e.target.value)}
            />
          </div>

          <button type="submit" className="btn" style={{ width: '100%', marginTop: '10px' }} disabled={saving}>
            {saving ? 'Saving...' : 'Save Changes'}
          </button>
        </form>
      </div>
    </div>
  );
}
