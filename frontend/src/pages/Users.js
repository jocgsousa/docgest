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
import Pagination from '../components/Pagination';
import RegistrationLinkGenerator from '../components/RegistrationLinkGenerator';
import { formatErrors } from '../utils/fieldLabels';

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
      case 'active':
        return `
          background-color: #d1fae5;
          color: #059669;
        `;
      case 'inactive':
        return `
          background-color: #fee2e2;
          color: #dc2626;
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
    profissao_id: '',
    tipo_usuario: 3,
    empresa_id: '',
    filial_id: ''
  });
  const [availableBranches, setAvailableBranches] = useState([]);
  const [professions, setProfessions] = useState([]);
  const [filters, setFilters] = useState({
    search: '',
    tipo_usuario: '',
    empresa_id: '',
    status: '1' // Por padrão, mostrar apenas usuários ativos
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [showDeletionModal, setShowDeletionModal] = useState(false);
  const [deletionUser, setDeletionUser] = useState(null);
  const [deletionData, setDeletionData] = useState({
    motivo: '',
    detalhes: ''
  });
  const [showLinkGenerator, setShowLinkGenerator] = useState(false);

  const userTypes = {
    1: 'Super Admin',
    2: 'Admin Empresa',
    3: 'Assinante'
  };

  const statusTypes = {
    '1': 'Ativo',
    '0': 'Inativo'
  };

  const deletionReasons = {
    'inatividade': 'Inatividade do usuário',
    'mudanca_empresa': 'Mudança de empresa',
    'solicitacao_titular': 'Solicitação do titular dos dados',
    'violacao_politica': 'Violação de política',
    'lgpd': 'LGPD',
    'outros': 'Outros motivos'
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
      key: 'ativo',
      title: 'Status',
      render: (value) => (
        <StatusBadge status={value ? 'active' : 'inactive'}>
          {value ? 'Ativo' : 'Inativo'}
        </StatusBadge>
      )
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
          {row.ativo ? (
            <Button
              size="sm"
              $variant="warning"
              onClick={() => handleDeactivate(row.id)}
            >
              Desativar
            </Button>
          ) : (
            <Button
              size="sm"
              $variant="success"
              onClick={() => handleActivate(row.id)}
            >
              Ativar
            </Button>
          )}
          {user?.tipo_usuario === 1 && (
            <Button
              size="sm"
              $variant="danger"
              onClick={() => handlePermanentDelete(row.id)}
            >
              Excluir Definitivo
            </Button>
          )}
          {user?.tipo_usuario === 2 && (
            <Button
              size="sm"
              $variant="warning"
              onClick={() => handleRequestDeletion(row)}
            >
              Criar Solicitação
            </Button>
          )}
        </div>
      )
    }
  ];

  // Carregar profissões
  const loadProfessions = async () => {
    try {
      const response = await api.get('/professions/all');
      setProfessions(response.data.data || []);
    } catch (error) {
      console.error('Erro ao carregar profissões:', error);
    }
  };

  useEffect(() => {
    fetchUsers();
    if (user?.tipo_usuario === 1 && companies.length === 0) {
      loadCompanies();
    }
    if ((user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && branches.length === 0) {
      loadBranches();
    }
    if (professions.length === 0) {
      loadProfessions();
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
      
      // Super admin e admin de empresa podem ver usuários inativos
      if ((user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && filters.status === '0') {
        params.append('incluir_inativos', 'true');
      }
      
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
        setErrors(formatErrors(error.response.data.errors));
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
      profissao_id: userToEdit.profissao_id || '',
      tipo_usuario: userToEdit.tipo_usuario,
      empresa_id: userToEdit.empresa_id || '',
      filial_id: userToEdit.filial_id || ''
    });
    setShowModal(true);
  };

  const handleDeactivate = async (id) => {
    if (window.confirm('Tem certeza que deseja desativar este usuário?')) {
      try {
        await api.put(`/users/${id}?action=deactivate`);
        fetchUsers();
      } catch (error) {
        console.error('Erro ao desativar usuário:', error);
        alert('Erro ao desativar usuário. Tente novamente.');
      }
    }
  };

  const handleActivate = async (id) => {
    if (window.confirm('Tem certeza que deseja ativar este usuário?')) {
      try {
        await api.put(`/users/${id}?action=activate`);
        fetchUsers();
      } catch (error) {
        console.error('Erro ao ativar usuário:', error);
        alert('Erro ao ativar usuário. Tente novamente.');
      }
    }
  };

  const handleRequestDeletion = (user) => {
    setDeletionUser(user);
    setDeletionData({ motivo: '', detalhes: '' });
    setShowDeletionModal(true);
  };

  const handleSubmitDeletion = async (e) => {
    e.preventDefault();
    if (!deletionData.motivo) {
      alert('Por favor, selecione um motivo para a solicitação.');
      return;
    }
    if (deletionData.motivo === 'outro' && !deletionData.detalhes.trim()) {
      alert('Por favor, especifique o motivo da solicitação.');
      return;
    }

    try {
      setSubmitting(true);
      await api.post('/users/create-request', {
        usuario_id: deletionUser.id,
        motivo: deletionData.motivo,
        detalhes: deletionData.detalhes
      });
      alert('Solicitação de exclusão enviada com sucesso!');
      setShowDeletionModal(false);
      setDeletionUser(null);
      setDeletionData({ motivo: '', detalhes: '' });
    } catch (error) {
      console.error('Erro ao enviar solicitação:', error);
      alert('Erro ao enviar solicitação. Tente novamente.');
    } finally {
      setSubmitting(false);
    }
  };

  const handlePermanentDelete = async (id) => {
    if (window.confirm('ATENÇÃO: Esta ação irá excluir permanentemente o usuário e todos os seus dados. Esta ação não pode ser desfeita. Tem certeza que deseja continuar?')) {
      try {
        await api.delete(`/users/${id}/permanent`);
        fetchUsers();
      } catch (error) {
        console.error('Erro ao excluir usuário permanentemente:', error);
        alert('Erro ao excluir usuário. Tente novamente.');
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
      profissao_id: '',
      tipo_usuario: 3,
      empresa_id: '',
      filial_id: ''
    });
    setAvailableBranches([]);
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
  const showCompanySelect = user?.tipo_usuario === 1 || user?.tipo_usuario === 2;
  const showBranchSelect = formData.tipo_usuario == 3; // Mostrar filial apenas para assinantes

  // Carregar filiais quando empresa for selecionada
  useEffect(() => {
    if (formData.empresa_id && showBranchSelect && Array.isArray(branches)) {
      const companyBranches = branches.filter(branch => branch.empresa_id == formData.empresa_id);
      setAvailableBranches(companyBranches);
      // Limpar filial selecionada se não estiver mais disponível
      if (formData.filial_id && !companyBranches.find(b => b.id == formData.filial_id)) {
        setFormData(prev => ({ ...prev, filial_id: '' }));
      }
    } else {
      setAvailableBranches([]);
      setFormData(prev => ({ ...prev, filial_id: '' }));
    }
  }, [formData.empresa_id, formData.tipo_usuario, branches]);

  // Limpar empresa e filial quando tipo de usuário mudar
  useEffect(() => {
    if (formData.tipo_usuario == 1) {
      setFormData(prev => ({ ...prev, empresa_id: '', filial_id: '' }));
    } else if (formData.tipo_usuario == 2) {
      setFormData(prev => ({ ...prev, filial_id: '' }));
    }
  }, [formData.tipo_usuario]);

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Usuários</PageTitle>
        {canCreateUser && (
          <div style={{ display: 'flex', gap: '12px' }}>
            <Button onClick={() => setShowModal(true)}>
              Novo Usuário
            </Button>
            <Button 
              $variant="outline" 
              onClick={() => setShowLinkGenerator(true)}
            >
              Gerar Link de Cadastro
            </Button>
          </div>
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

        {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && (
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
            <option value="1">Usuários Ativos</option>
            <option value="0">Usuários Inativos</option>
          </select>
        )}
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={users}
          loading={loading}
          emptyMessage="Nenhum usuário encontrado"
        />
        
        <Pagination
          currentPage={pagination.page}
          totalPages={Math.ceil(pagination.total / pagination.pageSize)}
          totalItems={pagination.total}
          pageSize={pagination.pageSize}
          onPageChange={handlePageChange}
          onPageSizeChange={(pageSize) => setPagination(prev => ({ ...prev, pageSize, page: 1 }))}
          pageSizeOptions={[10, 25, 50, 100]}
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
            
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Profissão
              </label>
              <select
                value={formData.profissao_id}
                onChange={(e) => setFormData(prev => ({ ...prev, profissao_id: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
                required
              >
                <option value="">Selecione uma profissão</option>
                {professions.map(profession => (
                  <option key={profession.id} value={profession.id}>
                    {profession.nome}
                  </option>
                ))}
              </select>
              {errors.profissao_id && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.profissao_id[0]}
                </span>
              )}
            </div>
            
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
            
            {showBranchSelect && formData.empresa_id && (
              <div>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Filial
                </label>
                <select
                  value={formData.filial_id}
                  onChange={(e) => setFormData(prev => ({ ...prev, filial_id: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                  required
                >
                  <option value="">Selecione uma filial</option>
                  {availableBranches.map(branch => (
                    <option key={branch.id} value={branch.id}>
                      {branch.nome}
                    </option>
                  ))}
                </select>
                {errors.filial_id && (
                  <span style={{ color: '#dc2626', fontSize: '12px' }}>
                    {errors.filial_id[0]}
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

      <Modal
        isOpen={showDeletionModal}
        onClose={() => {
          setShowDeletionModal(false);
          setDeletionUser(null);
          setDeletionData({ motivo: '', detalhes: '' });
        }}
        title="Criar Solicitação para Usuário"
      >
        <form onSubmit={handleSubmitDeletion}>
          <div style={{ marginBottom: '16px' }}>
            <p style={{ marginBottom: '16px', color: '#6b7280' }}>
              Você está criando uma solicitação para o usuário: <strong>{deletionUser?.nome}</strong>
            </p>
            
            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Motivo da solicitação *
              </label>
              <select
                value={deletionData.motivo}
                onChange={(e) => setDeletionData(prev => ({ ...prev, motivo: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
                required
              >
                <option value="">Selecione um motivo</option>
                {Object.entries(deletionReasons).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </div>

            {deletionData.motivo === 'outros' && (
              <div style={{ marginBottom: '16px' }}>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Especifique o motivo *
                </label>
                <textarea
                  value={deletionData.detalhes}
                  onChange={(e) => setDeletionData(prev => ({ ...prev, detalhes: e.target.value }))}
                  placeholder="Descreva o motivo da solicitação..."
                  rows={4}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px',
                    resize: 'vertical'
                  }}
                  required
                />
              </div>
            )}

            {deletionData.motivo && deletionData.motivo !== 'outro' && (
              <div style={{ marginBottom: '16px' }}>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Detalhes adicionais (opcional)
                </label>
                <textarea
                  value={deletionData.detalhes}
                  onChange={(e) => setDeletionData(prev => ({ ...prev, detalhes: e.target.value }))}
                  placeholder="Informações adicionais sobre a solicitação..."
                  rows={3}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px',
                    resize: 'vertical'
                  }}
                />
              </div>
            )}

            <div style={{ 
              padding: '12px', 
              backgroundColor: '#fef3c7', 
              border: '1px solid #f59e0b', 
              borderRadius: '6px',
              marginBottom: '16px'
            }}>
              <p style={{ margin: 0, fontSize: '14px', color: '#92400e' }}>
                <strong>Atenção:</strong> Esta solicitação será enviada aos administradores do sistema para análise. 
              </p>
            </div>
          </div>
          
          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              $variant="outline"
              onClick={() => {
                setShowDeletionModal(false);
                setDeletionUser(null);
                setDeletionData({ motivo: '', detalhes: '' });
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting} $variant="warning">
              Enviar Solicitação
            </Button>
          </div>
        </form>
      </Modal>

      <RegistrationLinkGenerator
        isOpen={showLinkGenerator}
        onClose={() => setShowLinkGenerator(false)}
        companies={companies}
        userCompanyId={user?.empresa_id}
      />
    </PageContainer>
  );
};

export default Users;