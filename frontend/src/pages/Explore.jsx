import React, { useState, useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Explore({ user }) {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  
  const [type, setType] = useState(searchParams.get('type') || 'all');
  const [location, setLocation] = useState(searchParams.get('search') || '');
  const [priceMax, setPriceMax] = useState(2000);
  const [packages, setPackages] = useState([]);
  const [loading, setLoading] = useState(true);

  // Sync state with URL params
  useEffect(() => {
    setType(searchParams.get('type') || 'all');
    setLocation(searchParams.get('search') || '');
  }, [searchParams]);

  // Fetch package listings based on search parameters
  useEffect(() => {
    setLoading(true);
    const params = {};
    if (type !== 'all') params.type = type;
    if (location) params.location = location;
    if (priceMax) params.price_max = priceMax;

    axios.get('/api/packages', { params })
      .then(res => {
        setPackages(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  }, [type, location, priceMax]);

  return (
    <div className="container" style={{ display: 'flex', gap: '30px', margin: '40px auto', flexWrap: 'wrap' }}>
      {/* Sidebar Filters */}
      <aside className="glass" style={{
        flex: '1',
        minWidth: '250px',
        maxWidth: '350px',
        padding: '25px',
        borderRadius: '12px',
        alignSelf: 'flex-start',
        border: '1px solid var(--glass-border)',
        boxShadow: 'var(--shadow-sm)'
      }}>
        <h3 style={{ marginBottom: '20px', color: 'var(--primary)' }}>Filter & Search</h3>
        
        <div className="form-group">
          <label>Type</label>
          <select 
            className="form-control" 
            value={type} 
            onChange={e => {
              setType(e.target.value);
              setSearchParams({ type: e.target.value, search: location });
            }}
          >
            <option value="all">All (Tours & Hotels)</option>
            <option value="tour">Tours Only</option>
            <option value="hotel">Hotels Only</option>
          </select>
        </div>

        <div className="form-group">
          <label>Location</label>
          <input 
            type="text" 
            className="form-control" 
            placeholder="e.g. Dubai, Switzerland..." 
            value={location}
            onChange={e => {
              setLocation(e.target.value);
              setSearchParams({ type, search: e.target.value });
            }}
          />
        </div>

        <div className="form-group">
          <label style={{ display: 'flex', justifyContent: 'space-between' }}>
            <span>Max Price</span>
            <span style={{ color: 'var(--primary)', fontWeight: 700 }}>${priceMax}</span>
          </label>
          <input 
            type="range" 
            min="100" 
            max="3000" 
            step="100" 
            style={{ width: '100%', accentColor: 'var(--primary)' }}
            value={priceMax} 
            onChange={e => setPriceMax(Number(e.target.value))}
          />
        </div>
      </aside>

      {/* Results Section */}
      <main style={{ flex: '3', minWidth: '300px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
          <h2>Discover Packages</h2>
          <span style={{ 
            background: 'var(--primary)', 
            color: 'white', 
            padding: '5px 15px', 
            borderRadius: '20px', 
            fontWeight: 700 
          }}>{packages.length} Results</span>
        </div>

        {loading ? (
          <div style={{ textAlign: 'center', padding: '50px' }}><h3>Loading Packages...</h3></div>
        ) : packages.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '50px', background: 'white', borderRadius: '12px', border: '1px dashed #ccc' }}>
            <p style={{ color: 'var(--text-muted)' }}>No listings found matching your criteria.</p>
          </div>
        ) : (
          <div className="grid" style={{ margin: 0 }}>
            {packages.map(pkg => (
              <div key={pkg.id} className="card" onClick={() => navigate(`/packages/${pkg.id}`)} style={{ cursor: 'pointer' }}>
                <div className="card-img" style={{ backgroundImage: `url(${pkg.image_url || 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800'})` }}>
                  <span className="card-badge">{pkg.type === 'hotel' ? 'Hotel' : 'Tour'}</span>
                </div>
                <div className="card-body">
                  <div className="card-meta">
                    <span>📍 {pkg.location}</span>
                    <span>By {pkg.company_name}</span>
                  </div>
                  <h3 className="card-title">{pkg.title}</h3>
                  <p className="card-price">${pkg.price}</p>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '20px', flexGrow: 1 }}>
                    {pkg.description.substring(0, 100)}...
                  </p>
                  <button className="btn" style={{ width: '100%' }}>View Details</button>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
