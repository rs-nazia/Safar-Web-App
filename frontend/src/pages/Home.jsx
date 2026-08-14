import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Home() {
  const navigate = useNavigate();
  const [packages, setPackages] = useState([]);
  const [search, setSearch] = useState('');
  const [currentSlide, setCurrentSlide] = useState(0);

  const slides = [
    'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1280&q=50',
    'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1280&q=50',
    'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1280&q=50',
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1280&q=50',
    'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1280&q=50'
  ];

  // Rotate hero slides
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide(prev => (prev + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  // Fetch top 3 packages
  useEffect(() => {
    axios.get('/api/packages')
      .then(res => {
        setPackages(res.data.slice(0, 3));
      })
      .catch(err => console.error(err));
  }, []);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    navigate(`/explore?search=${search}`);
  };

  return (
    <div>
      {/* Hero Section */}
      <section style={{
        position: 'relative',
        height: '500px',
        overflow: 'hidden',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#white',
        textAlign: 'center',
        padding: '0 20px',
        marginTop: '-110px',
        paddingTop: '110px'
      }}>
        {slides.map((slide, index) => (
          <div key={index} style={{
            position: 'absolute',
            top: 0, left: 0, width: 100 + '%', height: 100 + '%',
            backgroundImage: `url(${slide})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            opacity: currentSlide === index ? 1 : 0,
            transition: 'opacity 1s ease',
            zIndex: -2
          }} />
        ))}
        {/* Dark Overlay */}
        <div style={{
          position: 'absolute',
          top: 0, left: 0, width: 100 + '%', height: 100 + '%',
          background: 'rgba(0,0,0,0.4)',
          zIndex: -1
        }} />

        <div className="container" style={{ zIndex: 1, color: '#fff' }}>
          <h1 style={{ fontSize: '3rem', fontWeight: 800, marginBottom: '15px' }}>Discover Your Next Adventure</h1>
          <p style={{ fontSize: '1.2rem', marginBottom: '30px', opacity: 0.9 }}>Find the best tour packages from verified agencies worldwide. Book easily, travel safely.</p>
          
          {/* Hero Search Box */}
          <div style={{
            background: 'rgba(255, 255, 255, 0.95)',
            padding: '25px',
            borderRadius: '12px',
            boxShadow: 'var(--shadow-lg)',
            maxWidth: '800px',
            margin: '0 auto'
          }}>
            <form onSubmit={handleSearchSubmit} style={{ display: 'flex', gap: '15px', flexWrap: 'wrap', alignItems: 'flex-end' }}>
              <div style={{ flex: '2', minWidth: '200px', textAlign: 'left' }}>
                <label style={{ fontWeight: 600, color: 'var(--text-main)', fontSize: '0.9rem', marginBottom: '5px', display: 'block' }}>Destination</label>
                <input 
                  type="text" 
                  className="form-control" 
                  placeholder="Where are you going?" 
                  value={search} 
                  onChange={e => setSearch(e.target.value)} 
                />
              </div>
              <div style={{ flex: '1', minWidth: '120px' }}>
                <button type="submit" className="btn" style={{ width: '100%', height: '48px' }}>Search</button>
              </div>
            </form>
          </div>
        </div>
      </section>

      {/* Featured Tours Section */}
      <section className="container" style={{ margin: '80px auto' }}>
        <h2 style={{ fontSize: '2rem', fontWeight: 800, textAlign: 'center', marginBottom: '40px' }}>Featured Packages</h2>
        <div className="grid">
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
      </section>
    </div>
  );
}
