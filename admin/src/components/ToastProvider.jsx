import React, { createContext, useContext, useState } from 'react';
import { Toast } from './ui/Toast';

const ToastContext = createContext();

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const showToast = (message, type = 'success', duration = 3000) => {
    const id = Date.now() + Math.random();
    const newToast = { id, message, type, duration };
    setToasts(prev => [...prev, newToast]);
    
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
    
    return id;
  };

  const removeToast = (id) => {
    setToasts(prev => prev.filter(toast => toast.id !== id));
  };

  return (
    <ToastContext.Provider value={{ showToast, removeToast }}>
      {children}
      <div style={{ 
        position: 'fixed', 
        top: '32px', 
        right: '32px', 
        zIndex: 10000, 
        display: 'flex', 
        flexDirection: 'column', 
        gap: '12px', 
        pointerEvents: 'none',
        maxWidth: '500px'
      }}>
        {toasts.map((toast) => (
          <div
            key={toast.id}
            style={{
              pointerEvents: 'auto',
              animation: 'slideInRight 0.3s ease-out',
            }}
          >
            <Toast
              message={toast.message}
              type={toast.type}
              onClose={() => removeToast(toast.id)}
            />
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    // Fallback se não estiver dentro do provider
    return {
      showToast: (msg, type) => console.log(`[Toast ${type}]:`, msg),
      removeToast: () => {},
      ToastContainer: () => null
    };
  }
  return context;
}
