import React, { createContext, useContext, useState, useEffect } from 'react';
import { publicApi } from '../services/api';

const AppContext = createContext();

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp deve ser usado dentro de um AppProvider');
  }
  return context;
};

export const AppProvider = ({ children }) => {
  const [appInfo, setAppInfo] = useState({
    app_name: '', // Será carregado da API
    version: '1.0.0',
    api_version: 'v1'
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const loadAppInfo = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('🔄 Iniciando carregamento das informações da aplicação...');
      console.log('🌐 URL da API:', 'http://localhost:8000/api/app-info');
      
      const response = await publicApi.getAppInfo();
      console.log('📡 Resposta completa da API:', response);
      console.log('📊 Status da resposta:', response.status);
      console.log('📋 Headers da resposta:', response.headers);
      console.log('💾 Dados da resposta:', response.data);
      
      if (response.data && response.data.success && response.data.data) {
        console.log('✅ Dados válidos recebidos:', response.data.data);
        console.log('🏷️ Nome da aplicação recebido:', response.data.data.app_name);
        setAppInfo(response.data.data);
        console.log('💾 Estado atualizado com:', response.data.data);
      } else {
        console.warn('⚠️ Estrutura de resposta inesperada:', response.data);
      }
    } catch (err) {
      console.error('❌ Erro ao carregar informações da aplicação:', err);
      console.error('🔍 Detalhes do erro:', err.response || err.message);
      setError(err.message);
      // Manter valores padrão em caso de erro
    } finally {
      setLoading(false);
      console.log('🏁 Carregamento finalizado');
    }
  };

  useEffect(() => {
    loadAppInfo();
  }, []);

  const value = {
    appInfo,
    loading,
    error,
    refreshAppInfo: loadAppInfo
  };

  return (
    <AppContext.Provider value={value}>
      {children}
    </AppContext.Provider>
  );
};

export default AppContext;