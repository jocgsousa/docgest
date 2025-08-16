import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import { useData } from '../contexts/DataContext';
import api from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Table from '../components/Table';
import Modal from '../components/Modal';

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

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
`;

const FormRow = styled.div`
  grid-column: 1 / -1;
`;

const StatusBadge = styled.span`
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  
  ${props => props.status === 'ativo' && `
    background-color: #dcfce7;
    color: #166534;
  `}
  
  ${props => props.status === 'inativo' && `
    background-color: #fee2e2;
    color: #991b1b;
  `}
`;

function Branches() {
  const { user } = useAuth();
  const { refreshAll } = useData();
  const [branches, setBranches] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [editingBranch, setEditingBranch] = useState(null);
  const [formData, setFormData] = useState({
    empresa_id: '',
    nome: '',
    cnpj: '',
    inscricao_estadual: '',
    endereco: '',
    cidade: '',
    estado: '',
    cep: '',
    telefone: '',
    email: '',
    responsavel: '',
    observacoes: '',
    status: 'ativo'
  });
  const [companies, setCompanies] = useState([]);
  const [filters, setFilters] = useState({
    search: '',
    status: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const columns = [
    {
      key: 'nome',
      title: 'Nome',
      render: (value) => value || '-'
    },
    {
      key: 'endereco',
      title: 'Endereço',
      render: (value) => value || '-'
    },
    {
      key: 'telefone',
      title: 'Telefone',
      render: (value) => value || '-'
    },
    {
      key: 'responsavel',
      title: 'Responsável',
      render: (value) => value || '-'
    },
    {
      key: 'status',
      title: 'Status',
      render: (value) => (
        <StatusBadge status={value}>
          {value === 'ativo' ? 'Ativo' : 'Inativo'}
        </StatusBadge>
      )
    },
    {
      key: 'actions',
      title: 'Ações',
      render: (_, branch) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleEdit(branch)}
          >
            Editar
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDelete(branch.id)}
          >
            Excluir
          </Button>
        </div>
      )
    }
  ];

  useEffect(() => {
    fetchBranches();
    fetchCompanies();
  }, [pagination.page, filters]);

  const fetchCompanies = async () => {
    try {
      const response = await api.get('/companies');
      setCompanies(response.data.data?.items || []);
    } catch (error) {
      console.error('Erro ao carregar empresas:', error);
    }
  };

  const fetchBranches = async () => {
    try {
      setLoading(true);
      const params = {
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      };
      
      const response = await api.get('/branches', { params });
      setBranches(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao carregar filiais:', error);
      setError('Erro ao carregar filiais');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');

    try {
      if (editingBranch) {
        await api.put(`/branches/${editingBranch.id}`, formData);
      } else {
        await api.post('/branches', formData);
      }
      
      setShowModal(false);
      setEditingBranch(null);
      setFormData({
        empresa_id: '',
        nome: '',
        cnpj: '',
        inscricao_estadual: '',
        endereco: '',
        cidade: '',
        estado: '',
        cep: '',
        telefone: '',
        email: '',
        responsavel: '',
        observacoes: '',
        status: 'ativo'
      });
      fetchBranches();
    } catch (error) {
      setError(error.response?.data?.message || 'Erro ao salvar filial');
    } finally {
      setSubmitting(false);
      // Atualizar dados do contexto
      refreshAll();
    }
  };

  const handleEdit = (branch) => {
    setEditingBranch(branch);
    setFormData({
      empresa_id: branch.empresa_id || '',
      nome: branch.nome || '',
      cnpj: branch.cnpj || '',
      inscricao_estadual: branch.inscricao_estadual || '',
      endereco: branch.endereco || '',
      cidade: branch.cidade || '',
      estado: branch.estado || '',
      cep: branch.cep || '',
      telefone: branch.telefone || '',
      email: branch.email || '',
      responsavel: branch.responsavel || '',
      observacoes: branch.observacoes || '',
      status: branch.status || 'ativo'
    });
    setShowModal(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Tem certeza que deseja excluir esta filial?')) {
      return;
    }

    try {
      await api.delete(`/branches/${id}`);
      fetchBranches();
    } catch (error) {
      setError(error.response?.data?.message || 'Erro ao excluir filial');
    } finally {
      // Atualizar dados do contexto
      refreshAll();
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, page }));
  };

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Filiais</PageTitle>
        <Button onClick={() => setShowModal(true)}>
          Nova Filial
        </Button>
      </PageHeader>

      <FiltersContainer>
        <Input
          placeholder="Buscar filiais..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
        <select
          value={filters.status}
          onChange={(e) => handleFilterChange('status', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px'
          }}
        >
          <option value="">Todos os status</option>
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>
      </FiltersContainer>

      {error && (
        <div style={{ 
          padding: '12px', 
          backgroundColor: '#fee2e2', 
          color: '#991b1b', 
          borderRadius: '6px', 
          marginBottom: '16px' 
        }}>
          {error}
        </div>
      )}

      <Card>
        <Table
          columns={columns}
          data={branches}
          loading={loading}
          pagination={{
            current: pagination.page,
            pageSize: pagination.pageSize,
            total: pagination.total,
            onChange: handlePageChange
          }}
        />
      </Card>

      <Modal
        isOpen={showModal}
        onClose={() => {
          setShowModal(false);
          setEditingBranch(null);
          setFormData({
            empresa_id: '',
            nome: '',
            cnpj: '',
            inscricao_estadual: '',
            endereco: '',
            cidade: '',
            estado: '',
            cep: '',
            telefone: '',
            email: '',
            responsavel: '',
            observacoes: '',
            status: 'ativo'
          });
          setError('');
        }}
        title={editingBranch ? 'Editar Filial' : 'Nova Filial'}
      >
        <form onSubmit={handleSubmit}>
          <FormRow>
            <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
              Empresa *
            </label>
            <select
              value={formData.empresa_id}
              onChange={(e) => setFormData({ ...formData, empresa_id: e.target.value })}
              required
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px',
                backgroundColor: 'white'
              }}
            >
              <option value="">Selecione uma empresa</option>
              {companies.map(company => (
                <option key={company.id} value={company.id}>
                  {company.nome}
                </option>
              ))}
            </select>
          </FormRow>
          
          <FormGrid>
            <Input
              label="Nome *"
              value={formData.nome}
              onChange={(e) => setFormData({ ...formData, nome: e.target.value })}
              required
            />
            <Input
              label="CNPJ"
              value={formData.cnpj}
              onChange={(e) => setFormData({ ...formData, cnpj: e.target.value })}
              placeholder="00.000.000/0000-00"
            />
          </FormGrid>
          
          <FormGrid>
            <Input
              label="Inscrição Estadual"
              value={formData.inscricao_estadual}
              onChange={(e) => setFormData({ ...formData, inscricao_estadual: e.target.value })}
            />
            <Input
              label="Telefone"
              value={formData.telefone}
              onChange={(e) => setFormData({ ...formData, telefone: e.target.value })}
            />
          </FormGrid>
          
          <FormRow>
            <Input
              label="Endereço"
              value={formData.endereco}
              onChange={(e) => setFormData({ ...formData, endereco: e.target.value })}
            />
          </FormRow>
          
          <FormGrid>
            <Input
              label="Cidade"
              value={formData.cidade}
              onChange={(e) => setFormData({ ...formData, cidade: e.target.value })}
            />
            <Input
              label="Estado"
              value={formData.estado}
              onChange={(e) => setFormData({ ...formData, estado: e.target.value })}
              placeholder="SP"
              maxLength="2"
            />
          </FormGrid>
          
          <FormGrid>
            <Input
              label="CEP"
              value={formData.cep}
              onChange={(e) => setFormData({ ...formData, cep: e.target.value })}
              placeholder="00000-000"
            />
            <div></div>
          </FormGrid>
          
          <FormGrid>
            <Input
              label="E-mail"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
            />
            <Input
              label="Responsável"
              value={formData.responsavel}
              onChange={(e) => setFormData({ ...formData, responsavel: e.target.value })}
            />
          </FormGrid>
          
          <FormRow>
            <Input
              label="Observações"
              value={formData.observacoes}
              onChange={(e) => setFormData({ ...formData, observacoes: e.target.value })}
              multiline
              rows={3}
            />
          </FormRow>
          
          <FormRow>
            <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
              Status
            </label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            >
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
            </select>
          </FormRow>

          {error && (
            <div style={{ 
              padding: '12px', 
              backgroundColor: '#fee2e2', 
              color: '#991b1b', 
              borderRadius: '6px', 
              marginTop: '16px' 
            }}>
              {error}
            </div>
          )}

          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end', marginTop: '24px' }}>
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowModal(false)}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingBranch ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
}

export default Branches;