import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';
import { useAuth } from './AuthContext';

const DataContext = createContext();

export const useData = () => {
  const context = useContext(DataContext);
  if (!context) {
    throw new Error('useData deve ser usado dentro de um DataProvider');
  }
  return context;
};

export const DataProvider = ({ children }) => {
  const { user, isAdmin, isCompanyAdmin } = useAuth();
  const [companies, setCompanies] = useState([]);
  const [plans, setPlans] = useState([]);
  const [branches, setBranches] = useState([]);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState({
    companies: false,
    plans: false,
    branches: false,
    users: false
  });
  const [error, setError] = useState(null);

  // Carregar empresas (apenas para admins)
  const loadCompanies = async (params = {}) => {
    if (!isAdmin) return;
    
    try {
      setLoading(prev => ({ ...prev, companies: true }));
      const queryParams = new URLSearchParams({
        page_size: 1000, // Carregar todas para uso em selects
        ...params
      });
      
      const response = await api.get(`/companies?${queryParams}`);
      setCompanies(response.data.data?.items || []);
    } catch (error) {
      console.error('Erro ao carregar empresas:', error);
      setError('Erro ao carregar empresas');
    } finally {
      setLoading(prev => ({ ...prev, companies: false }));
    }
  };

  // Carregar planos
  const loadPlans = async (params = {}) => {
    try {
      setLoading(prev => ({ ...prev, plans: true }));
      
      const response = await api.get('/plans/all');
      setPlans(response.data.data || []);
    } catch (error) {
      console.error('Erro ao carregar planos:', error);
      setError('Erro ao carregar planos');
    } finally {
      setLoading(prev => ({ ...prev, plans: false }));
    }
  };

  // Carregar filiais (apenas para admins de empresa)
  const loadBranches = async (params = {}) => {
    if (!isCompanyAdmin && !isAdmin) return;
    
    try {
      setLoading(prev => ({ ...prev, branches: true }));
      const queryParams = new URLSearchParams({
        page_size: 1000, // Carregar todas para uso em selects
        ...params
      });
      
      const response = await api.get(`/branches?${queryParams}`);
      setBranches(response.data.data || []);
    } catch (error) {
      console.error('Erro ao carregar filiais:', error);
      setError('Erro ao carregar filiais');
    } finally {
      setLoading(prev => ({ ...prev, branches: false }));
    }
  };

  // Carregar usuários
  const loadUsers = async (params = {}) => {
    try {
      setLoading(prev => ({ ...prev, users: true }));
      const queryParams = new URLSearchParams({
        page_size: 1000, // Carregar todos para uso em selects
        ...params
      });
      
      const response = await api.get(`/users?${queryParams}`);
      setUsers(response.data.data || []);
    } catch (error) {
      console.error('Erro ao carregar usuários:', error);
      setError('Erro ao carregar usuários');
    } finally {
      setLoading(prev => ({ ...prev, users: false }));
    }
  };

  // Recarregar todos os dados
  const refreshAll = async () => {
    const promises = [];
    
    if (isAdmin) {
      promises.push(loadCompanies());
    }
    
    promises.push(loadPlans());
    
    if (isCompanyAdmin || isAdmin) {
      promises.push(loadBranches());
    }
    
    promises.push(loadUsers());
    
    await Promise.all(promises);
  };

  // Limpar erro
  const clearError = () => {
    setError(null);
  };

  // Carregar dados iniciais quando o usuário estiver disponível
  useEffect(() => {
    if (user) {
      refreshAll();
    }
  }, [user, isAdmin, isCompanyAdmin]);

  // Funções de utilidade para buscar itens específicos
  const getCompanyById = (id) => {
    return companies.find(company => company.id === parseInt(id));
  };

  const getPlanById = (id) => {
    return plans.find(plan => plan.id === parseInt(id));
  };

  const getBranchById = (id) => {
    return branches.find(branch => branch.id === parseInt(id));
  };

  const getUserById = (id) => {
    return users.find(user => user.id === parseInt(id));
  };

  // Filtrar empresas por status
  const getActiveCompanies = () => {
    return companies.filter(company => {
      const today = new Date();
      const vencimento = new Date(company.data_vencimento);
      return vencimento >= today;
    });
  };

  // Filtrar planos ativos
  const getActivePlans = () => {
    return plans.filter(plan => plan.ativo !== false);
  };

  // Filtrar filiais ativas
  const getActiveBranches = () => {
    return branches.filter(branch => branch.ativo !== false);
  };

  // Filtrar usuários ativos
  const getActiveUsers = () => {
    return users.filter(user => user.ativo !== false);
  };

  const value = {
    // Dados
    companies,
    plans,
    branches,
    users,
    loading,
    error,
    
    // Funções de carregamento
    loadCompanies,
    loadPlans,
    loadBranches,
    loadUsers,
    refreshAll,
    clearError,
    
    // Funções de utilidade
    getCompanyById,
    getPlanById,
    getBranchById,
    getUserById,
    getActiveCompanies,
    getActivePlans,
    getActiveBranches,
    getActiveUsers
  };

  return (
    <DataContext.Provider value={value}>
      {children}
    </DataContext.Provider>
  );
};