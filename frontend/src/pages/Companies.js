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
  
  ${props => {
    const today = new Date();
    const vencimento = new Date(props.date);
    const diffTime = vencimento - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
      return `
        background-color: #fee2e2;
        color: #dc2626;
      `;
    } else if (diffDays <= 7) {
      return `
        background-color: #fef3c7;
        color: #d97706;
      `;
    } else {
      return `
        background-color: #d1fae5;
        color: #059669;
      `;
    }
  }}
`;

const Companies = ({ openCreateModal = false }) => {
  const { user } = useAuth();
  const { plans, loadPlans } = useData();
  const [companies, setCompanies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingCompany, setEditingCompany] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    cnpj: '',
    email: '',
    telefone: '',
    endereco: '',
    cidade: '',
    estado: '',
    cep: '',
    plano_id: '',
    data_vencimento: ''
  });
  const [filters, setFilters] = useState({
    search: '',
    plano_id: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const columns = [
    {
      key: 'nome',
      title: 'Nome',
      sortable: true
    },
    {
      key: 'cnpj',
      title: 'CNPJ',
      render: (value) => value ? value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : ''
    },
    {
      key: 'email',
      title: 'Email',
      sortable: true
    },
    {
      key: 'plano_nome',
      title: 'Plano'
    },
    {
      key: 'data_vencimento',
      title: 'Vencimento',
      render: (value) => {
        const date = new Date(value);
        const today = new Date();
        const diffTime = date - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        let status = 'Ativo';
        if (diffDays < 0) {
          status = 'Vencido';
        } else if (diffDays <= 7) {
          status = 'Vence em breve';
        }
        
        return (
          <div>
            <div>{date.toLocaleDateString('pt-BR')}</div>
            <StatusBadge date={value}>{status}</StatusBadge>
          </div>
        );
      }
    },
    {
      key: 'actions',
      title: 'Ações',
      render: (_, row) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button
            size="sm"
            $variant="outline"
            onClick={() => handleEdit(row)}
          >
            Editar
          </Button>
          {user?.tipo_usuario === 1 && (
            <Button
              size="sm"
              $variant="danger"
              onClick={() => handleDelete(row.id)}
            >
              Excluir
            </Button>
          )}
        </div>
      )
    }
  ];

  useEffect(() => {
    fetchCompanies();
    if (plans.length === 0) {
      loadPlans();
    }
  }, [filters, pagination.page]);

  useEffect(() => {
    if (openCreateModal) {
      setShowModal(true);
    }
  }, [openCreateModal]);

  const fetchCompanies = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/companies?${params}`);
      setCompanies(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar empresas:', error);
    } finally {
      setLoading(false);
    }
  };

  // Função fetchPlans removida - agora usa o DataContext

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      if (editingCompany) {
        await api.put(`/companies/${editingCompany.id}`, formData);
      } else {
        await api.post('/companies', formData);
      }
      
      setShowModal(false);
      setEditingCompany(null);
      resetForm();
      fetchCompanies();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = (companyToEdit) => {
    setEditingCompany(companyToEdit);
    setFormData({
      nome: companyToEdit.nome,
      cnpj: companyToEdit.cnpj,
      email: companyToEdit.email,
      telefone: companyToEdit.telefone,
      endereco: companyToEdit.endereco || '',
      cidade: companyToEdit.cidade || '',
      estado: companyToEdit.estado || '',
      cep: companyToEdit.cep || '',
      plano_id: companyToEdit.plano_id,
      data_vencimento: companyToEdit.data_vencimento
    });
    setShowModal(true);
  };

  const handleDelete = async (companyId) => {
    if (window.confirm('Tem certeza que deseja excluir esta empresa?')) {
      try {
        await api.delete(`/companies/${companyId}`);
        fetchCompanies();
      } catch (error) {
        console.error('Erro ao excluir empresa:', error);
      }
    }
  };

  const fetchAddressByCep = async (cep) => {
    const cleanCep = cep.replace(/\D/g, '');
    if (cleanCep.length === 8) {
      try {
        const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
        const data = await response.json();
        
        if (!data.erro) {
          setFormData(prev => ({
            ...prev,
            endereco: data.logradouro || '',
            cidade: data.localidade || '',
            estado: data.uf || '',
            cep: cleanCep
          }));
        }
      } catch (error) {
        console.error('Erro ao buscar CEP:', error);
      }
    }
  };

  const handleCepChange = (e) => {
    const cep = e.target.value;
    setFormData(prev => ({ ...prev, cep }));
    
    // Busca automática quando CEP tem 8 dígitos
    const cleanCep = cep.replace(/\D/g, '');
    if (cleanCep.length === 8) {
      fetchAddressByCep(cep);
    }
  };

  const resetForm = () => {
    setFormData({
      nome: '',
      cnpj: '',
      email: '',
      telefone: '',
      endereco: '',
      cidade: '',
      estado: '',
      cep: '',
      plano_id: '',
      data_vencimento: ''
    });
    setErrors({});
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }));
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, page }));
  };

  const canCreateCompany = user?.tipo_usuario === 1;
  const canEditPlan = user?.tipo_usuario === 1 || !editingCompany; // Permite seleção de plano ao criar nova empresa
  const canEditExpiration = user?.tipo_usuario === 1;

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Empresas</PageTitle>
        {canCreateCompany && (
          <Button onClick={() => setShowModal(true)}>
            Nova Empresa
          </Button>
        )}
      </PageHeader>

      <FiltersContainer>
        <Input
          placeholder="Buscar por nome, CNPJ ou email..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
        
        <select
          value={filters.plano_id}
          onChange={(e) => handleFilterChange('plano_id', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px'
          }}
        >
          <option value="">Todos os planos</option>
          {plans.map(plan => (
            <option key={plan.id} value={plan.id}>
              {plan.nome}
            </option>
          ))}
        </select>
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={companies}
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
          setEditingCompany(null);
          resetForm();
        }}
        title={editingCompany ? 'Editar Empresa' : 'Nova Empresa'}
        size="lg"
      >
        <form onSubmit={handleSubmit}>
          <FormGrid>
            <Input
              label="Nome da Empresa"
              value={formData.nome}
              onChange={(e) => setFormData(prev => ({ ...prev, nome: e.target.value }))}
              error={errors.nome?.[0]}
              required
            />
            
            <Input
              label="CNPJ"
              value={formData.cnpj}
              onChange={(e) => setFormData(prev => ({ ...prev, cnpj: e.target.value }))}
              error={errors.cnpj?.[0]}
              required
            />
            
            <Input
              label="Email"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
              error={errors.email?.[0]}
              required
            />
            
            <Input
              label="Telefone"
              value={formData.telefone}
              onChange={(e) => setFormData(prev => ({ ...prev, telefone: e.target.value }))}
              error={errors.telefone?.[0]}
              required
            />
            
            <Input
              label="Cidade"
              value={formData.cidade}
              onChange={(e) => setFormData(prev => ({ ...prev, cidade: e.target.value }))}
              error={errors.cidade?.[0]}
            />
            
            <Input
              label="Estado"
              value={formData.estado}
              onChange={(e) => setFormData(prev => ({ ...prev, estado: e.target.value }))}
              error={errors.estado?.[0]}
            />
            
            <Input
              label="CEP"
              value={formData.cep}
              onChange={handleCepChange}
              error={errors.cep?.[0]}
              placeholder="00000-000"
            />
            
            {canEditPlan && (
              <div>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Plano *
                </label>
                <select
                  value={formData.plano_id}
                  onChange={(e) => setFormData(prev => ({ ...prev, plano_id: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                  required
                >
                  <option value="">Selecione um plano</option>
                  {plans.map(plan => (
                    <option key={plan.id} value={plan.id}>
                      {plan.nome} - R$ {parseFloat(plan.preco).toFixed(2)}
                    </option>
                  ))}
                </select>
                {errors.plano_id && (
                  <span style={{ color: '#dc2626', fontSize: '12px' }}>
                    {errors.plano_id[0]}
                  </span>
                )}
              </div>
            )}
            
            {canEditExpiration && (
              <Input
                label="Data de Vencimento"
                type="date"
                value={formData.data_vencimento}
                onChange={(e) => setFormData(prev => ({ ...prev, data_vencimento: e.target.value }))}
                error={errors.data_vencimento?.[0]}
                required
              />
            )}
          </FormGrid>
          
          <FormRow>
            <Input
              label="Endereço"
              value={formData.endereco}
              onChange={(e) => setFormData(prev => ({ ...prev, endereco: e.target.value }))}
              error={errors.endereco?.[0]}
            />
          </FormRow>
          
          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              $variant="outline"
              onClick={() => {
                setShowModal(false);
                setEditingCompany(null);
                resetForm();
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingCompany ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
};

export default Companies;