import React, { useEffect } from 'react';
import { CheckCircle2, XCircle, AlertCircle, Info } from 'lucide-react';

export function Toast({ message, type = 'success', onClose, duration = 3000 }) {
  useEffect(() => {
    if (duration && onClose) {
      const timer = setTimeout(() => {
        onClose();
      }, duration);
      return () => clearTimeout(timer);
    }
  }, [duration, onClose]);

  const icons = {
    success: CheckCircle2,
    error: XCircle,
    warning: AlertCircle,
    info: Info,
  };

  const Icon = icons[type] || CheckCircle2;

  return (
    <div
      className={`acervox-toast acervox-toast-${type}`}
      style={{
        padding: '16px 20px',
        backgroundColor: type === 'success' ? 'hsl(142, 76%, 36%)' : type === 'error' ? 'hsl(0, 84%, 60%)' : type === 'warning' ? 'hsl(38, 92%, 50%)' : 'hsl(217, 91%, 60%)',
        color: 'white',
        borderRadius: '8px',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        minWidth: '300px',
        maxWidth: '500px',
      }}
    >
      <Icon size={20} />
      <div style={{ flex: 1, fontSize: '14px', fontWeight: 500 }}>{message}</div>
      {onClose && (
        <button
          onClick={onClose}
          style={{
            background: 'none',
            border: 'none',
            color: 'white',
            cursor: 'pointer',
            padding: '4px',
            display: 'flex',
            alignItems: 'center',
            fontSize: '18px',
            lineHeight: 1,
            opacity: 0.8,
          }}
        >
          ×
        </button>
      )}
    </div>
  );
}

export function useToast() {
  const [toasts, setToasts] = React.useState([]);

  const showToast = (message, type = 'success', duration = 3000) => {
    const id = Date.now();
    const newToast = { id, message, type, duration };
    setToasts(prev => [...prev, newToast]);
    return id;
  };

  const removeToast = (id) => {
    setToasts(prev => prev.filter(toast => toast.id !== id));
  };

  const ToastContainer = () => (
    <div style={{ position: 'fixed', top: '32px', right: '32px', zIndex: 10000, display: 'flex', flexDirection: 'column', gap: '12px' }}>
      {toasts.map((toast, index) => (
        <div
          key={toast.id}
          style={{
            animation: 'slideInRight 0.3s ease-out',
            transform: `translateY(${index * 70}px)`,
          }}
        >
          <Toast
            message={toast.message}
            type={toast.type}
            duration={toast.duration}
            onClose={() => removeToast(toast.id)}
          />
        </div>
      ))}
    </div>
  );

  return { showToast, removeToast, ToastContainer };
}
