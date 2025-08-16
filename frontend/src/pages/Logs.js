import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Table from '../components/Table';

const PageContainer = styled.div`
  padding: 24px;
`;

const PageHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
`;

const PageTitle = styled.h1`
  font-size: 24px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
`;

const FiltersContainer = styled.div`
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
`;

const LogLevel = styled.span`
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background-color: ${props => {
    switch (props.level) {
      case 'error': return '#fee2e2';
      case 'warning': return '#fef3c7';
      case 'info': return '#dbeafe';
      case 'debug': return '#f3f4f6';
      default: return '#f3f4f6';
    }
  }};
  color: ${props => {
    switch (props.level) {
      case 'error': return '#dc2626';
      case 'warning': return '#d97706';
      case 'info': return '#2563eb';
      case 'debug': return '#6b7280';
      default: return '#6b7280';
    }
  }};
`;

const Logs = () => {
  const { user } = useAuth();
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    search: '',
    level: '',
    date_from: '',
    date_to: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 20,
    total: 0
  });

  const levelOptions = [
    { value: '', label: 'Todos os níveis' },
    { value: 'error', label: 'Erro' },
    { value: 'warning', label: 'Aviso' },
    { value: 'info', label: 'Informação' },
    { value: 'debug', label: 'Debug' }
  ];

  const columns = [
    {
      key: 'created_at',
      title: 'Data/Hora',
      render: (value) => new Date(value).toLocaleString('pt-BR')
    },
    {
      key: 'level',
      title: 'Nível',
      render: (value) => <LogLevel level={value}>{value.toUpperCase()}</LogLevel>
    },
    {
      key: 'message',
      title: 'Mensagem',
      render: (value) => (
        <div style={{ maxWidth: '400px', wordBreak: 'break-word' }}>
          {value}
        </div>
      )
    },
    {
      key: 'context',
      title: 'Contexto',
      render: (value) => (
        <div style={{ maxWidth: '200px', wordBreak: 'break-word' }}>
          {value || '-'}
        </div>
      )
    },
    {
      key: 'user_id',
      title: 'Usuário',
      render: (value, row) => row.user_name || `ID: ${value || 'Sistema'}`
    }
  ];

  useEffect(() => {
    fetchLogs();
  }, [filters, pagination.page]);

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/logs?${params}`);
      setLogs(response.data.data || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar logs:', error);
      setLogs([]);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, page }));
  };

  const clearLogs = async () => {
    if (!window.confirm('Tem certeza que deseja limpar todos os logs? Esta ação não pode ser desfeita.')) {
      return;
    }

    try {
      await api.delete('/logs');
      fetchLogs();
    } catch (error) {
      console.error('Erro ao limpar logs:', error);
      alert('Erro ao limpar logs');
    }
  };

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Logs do Sistema</PageTitle>
        {user?.tipo_usuario === 1 && (
          <Button 
            $variant="danger" 
            onClick={clearLogs}
            disabled={loading || logs.length === 0}
          >
            Limpar Logs
          </Button>
        )}
      </PageHeader>

      <FiltersContainer>
        <Input
          placeholder="Buscar mensagem..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
        
        <select
          value={filters.level}
          onChange={(e) => handleFilterChange('level', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px'
          }}
        >
          {levelOptions.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>

        <Input
          type="date"
          placeholder="Data inicial"
          value={filters.date_from}
          onChange={(e) => handleFilterChange('date_from', e.target.value)}
        />

        <Input
          type="date"
          placeholder="Data final"
          value={filters.date_to}
          onChange={(e) => handleFilterChange('date_to', e.target.value)}
        />
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={logs}
          loading={loading}
          pagination={{
            current: pagination.page,
            pageSize: pagination.pageSize,
            total: pagination.total,
            onChange: handlePageChange
          }}
          emptyText="Nenhum log encontrado"
        />
      </Card>
    </PageContainer>
  );
};

export default Logs;