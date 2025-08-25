import { useState, useCallback, useRef } from 'react';

let toastId = 0;

function useToast() {
  const [toasts, setToasts] = useState([]);
  const timeoutsRef = useRef(new Map());

  const removeToast = useCallback((id) => {
    // Marcar como saindo para animação
    setToasts(prev => 
      prev.map(toast => 
        toast.id === id ? { ...toast, isExiting: true } : toast
      )
    );

    // Remover após animação
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
      
      // Limpar timeout se existir
      const timeout = timeoutsRef.current.get(id);
      if (timeout) {
        clearTimeout(timeout);
        timeoutsRef.current.delete(id);
      }
    }, 300); // Tempo da animação
  }, []);

  const addToast = useCallback((toast) => {
    const id = ++toastId;
    const duration = toast.duration || 5000; // 5 segundos por padrão
    
    const newToast = {
      id,
      type: toast.type || 'info',
      title: toast.title || '',
      message: toast.message || '',
      showProgress: toast.showProgress !== false,
      progress: 100,
      isExiting: false
    };

    setToasts(prev => [...prev, newToast]);

    // Auto-remover após duração especificada
    if (duration > 0) {
      // Animação da barra de progresso
      let progress = 100;
      const progressInterval = setInterval(() => {
        progress -= (100 / (duration / 100));
        if (progress <= 0) {
          clearInterval(progressInterval);
          return;
        }
        
        setToasts(prev => 
          prev.map(t => 
            t.id === id ? { ...t, progress } : t
          )
        );
      }, 100);

      const timeout = setTimeout(() => {
        clearInterval(progressInterval);
        removeToast(id);
      }, duration);

      timeoutsRef.current.set(id, timeout);
    }

    return id;
  }, [removeToast]);

  const showSuccess = useCallback((title, message, options = {}) => {
    return addToast({
      type: 'success',
      title,
      message,
      ...options
    });
  }, [addToast]);

  const showError = useCallback((title, message, options = {}) => {
    return addToast({
      type: 'error',
      title,
      message,
      duration: options.duration || 7000, // Erros ficam mais tempo
      ...options
    });
  }, [addToast]);

  const showInfo = useCallback((title, message, options = {}) => {
    return addToast({
      type: 'info',
      title,
      message,
      ...options
    });
  }, [addToast]);

  const clearAll = useCallback(() => {
    // Limpar todos os timeouts
    timeoutsRef.current.forEach(timeout => clearTimeout(timeout));
    timeoutsRef.current.clear();
    
    setToasts([]);
  }, []);

  return {
    toasts,
    addToast,
    removeToast,
    showSuccess,
    showError,
    showInfo,
    clearAll
  };
}

export default useToast;