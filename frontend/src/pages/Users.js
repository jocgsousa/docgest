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
    switch (props.status) {
      case 1:
        return `
          background-color: #fee2e2;
          color: #dc2626;
        `;
      case 2:
        return `
          background-color: #dbeafe;
          color: #2563eb;
        `;
      case 3:
        return `
          background-color: #d1fae5;
          color: #059669;
        `;
      default:
        return `
          background-color: #f3f4f6;
          color: #6b7280;
        `;
    }
  }}
`;

const Users = ({ openCreateModal = false }) => {
  const { user } = useAuth();
  const { companies, branches, loadCompanies, loadBranches } = useData();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    email: '',
    cpf: '',
    telefone: '',
    senha: '',
    tipo_usuario: 3,
    empresa_id: '',
    filial_id: ''
  });
  const [filters, setFilters] = useState({
    search: '',
    tipo_usuario: '',
    empresa_id: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const userTypes = {
    1: 'Super Admin',
    2: 'Admin Empresa',
    3: 'Assinante'
  };

  const columns = [
    {
      key: 'nome',
      title: 'Nome',
      sortable: true
    },
    {
      key: 'email',
      title: 'Email',
      sortable: true
    },
    {
      key: 'cpf',
      title: 'CPF',
      render: (value) => value ? value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : ''
    },
    {
      key: 'telefone',
      title: 'Telefone'
    },
    {
      key: 'tipo_usuario',
      title: 'Tipo',
      render: (value) => (
        <StatusBadge status={value}>
          {userTypes[value] || 'Desconhecido'}
        </StatusBadge>
      )
    },
    {
      key: 'empresa_nome',
      title: 'Empresa'
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
          <Button
            size="sm"
            $variant="danger"
            onClick={() => handleDelete(row.id)}
          >
            Excluir
          </Button>
        </div>
      )
    }
  ];

  useEffect(() => {
    fetchUsers();
    if (user?.tipo_usuario === 1 && companies.length === 0) {
      loadCompanies();
    }
    if ((user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && branches.length === 0) {
      loadBranches();
    }
  }, [filters, pagination.page]);

  // Abrir modal automaticamente quando openCreateModal for true
  useEffect(() => {
    if (openCreateModal) {
      setShowModal(true);
    }
  }, [openCreateModal]);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/users?${params}`);
      setUsers(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar usuários:', error);
    } finally {
      setLoading(false);
    }
  };

  // Função fetchCompanies removida - agora usa o DataContext

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      if (editingUser) {
        await api.put(`/users/${editingUser.id}`, formData);
      } else {
        await api.post('/users', formData);
      }
      
      setShowModal(false);
      setEditingUser(null);
      resetForm();
      fetchUsers();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = (userToEdit) => {
    setEditingUser(userToEdit);
    setFormData({
      nome: userToEdit.nome,
      email: userToEdit.email,
      cpf: userToEdit.cpf,
      telefone: userToEdit.telefone,
      senha: '',
      tipo_usuario: userToEdit.tipo_usuario,
      empresa_id: userToEdit.empresa_id || '',
      filial_id: userToEdit.filial_id || ''
    });
    setShowModal(true);
  };

  const handleDelete = async (userId) => {
    if (window.confirm('Tem certeza que deseja excluir este usuário?')) {
      try {
        await api.delete(`/users/${userId}`);
        fetchUsers();
      } catch (error) {
        console.error('Erro ao excluir usuário:', error);
      }
    }
  };

  const resetForm = () => {
    setFormData({
      nome: '',
      email: '',
      cpf: '',
      telefone: '',
      senha: '',
      tipo_usuario: 3,
      empresa_id: '',
      filial_id: ''
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

  const canCreateUser = user?.tipo_usuario === 1 || user?.tipo_usuario === 2;
  const showCompanyFilter = user?.tipo_usuario === 1;
  const showCompanySelect = user?.tipo_usuario === 1;

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Usuários</PageTitle>
        {canCreateUser && (
          <Button onClick={() => setShowModal(true)}>
            Novo Usuário
          </Button>
        )}
      </PageHeader>

      <FiltersContainer>
        <Input
          placeholder="Buscar por nome, email ou CPF..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
        
        <select
          value={filters.tipo_usuario}
          onChange={(e) => handleFilterChange('tipo_usuario', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px'
          }}
        >
          <option value="">Todos os tipos</option>
          {Object.entries(userTypes).map(([value, label]) => (
            <option key={value} value={value}>{label}</option>
          ))}
        </select>

        {showCompanyFilter && (
          <select
            value={filters.empresa_id}
            onChange={(e) => handleFilterChange('empresa_id', e.target.value)}
            style={{
              padding: '8px 12px',
              border: '1px solid #d1d5db',
              borderRadius: '6px',
              fontSize: '14px'
            }}
          >
            <option value="">Todas as empresas</option>
            {companies.map(company => (
              <option key={company.id} value={company.id}>
                {company.nome}
              </option>
            ))}
          </select>
        )}
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={users}
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
          setEditingUser(null);
          resetForm();
        }}
        title={editingUser ? 'Editar Usuário' : 'Novo Usuário'}
      >
        <form onSubmit={handleSubmit}>
          <FormGrid>
            <Input
              label="Nome"
              value={formData.nome}
              onChange={(e) => setFormData(prev => ({ ...prev, nome: e.target.value }))}
              error={errors.nome?.[0]}
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
              label="CPF"
              value={formData.cpf}
              onChange={(e) => setFormData(prev => ({ ...prev, cpf: e.target.value }))}
              error={errors.cpf?.[0]}
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
              label={editingUser ? 'Nova Senha (deixe vazio para manter)' : 'Senha'}
              type="password"
              value={formData.senha}
              onChange={(e) => setFormData(prev => ({ ...prev, senha: e.target.value }))}
              error={errors.senha?.[0]}
              required={!editingUser}
            />
            
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Tipo de Usuário
              </label>
              <select
                value={formData.tipo_usuario}
                onChange={(e) => setFormData(prev => ({ ...prev, tipo_usuario: parseInt(e.target.value) }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
                required
              >
                {user?.tipo_usuario === 1 && <option value={1}>Super Admin</option>}
                <option value={2}>Admin Empresa</option>
                <option value={3}>Assinante</option>
              </select>
              {errors.tipo_usuario && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.tipo_usuario[0]}
                </span>
              )}
            </div>
            
            {showCompanySelect && formData.tipo_usuario !== 1 && (
              <div>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Empresa
                </label>
                <select
                  value={formData.empresa_id}
                  onChange={(e) => setFormData(prev => ({ ...prev, empresa_id: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                  required
                >
                  <option value="">Selecione uma empresa</option>
                  {companies.map(company => (
                    <option key={company.id} value={company.id}>
                      {company.nome}
                    </option>
                  ))}
                </select>
                {errors.empresa_id && (
                  <span style={{ color: '#dc2626', fontSize: '12px' }}>
                    {errors.empresa_id[0]}
                  </span>
                )}
              </div>
            )}
          </FormGrid>
          
          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              $variant="outline"
              onClick={() => {
                setShowModal(false);
                setEditingUser(null);
                resetForm();
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingUser ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
};

export default Users;