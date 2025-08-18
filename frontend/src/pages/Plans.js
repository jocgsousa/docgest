import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Table from '../components/Table';
import Modal from '../components/Modal';
import Pagination from '../components/Pagination';
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

const PriceTag = styled.span`
  font-weight: 600;
  color: ${props => props.theme.colors.primary};
  font-size: 16px;
`;

const LimitBadge = styled.span`
  display: inline-block;
  padding: 2px 6px;
  background-color: ${props => props.theme.colors.background};
  border: 1px solid ${props => props.theme.colors.border};
  border-radius: 4px;
  font-size: 12px;
  margin-right: 4px;
  margin-bottom: 2px;
`;

const StatsGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
`;

const StatCard = styled(Card)`
  text-align: center;
  padding: 20px;
`;

const StatValue = styled.div`
  font-size: 24px;
  font-weight: 600;
  color: ${props => props.theme.colors.primary};
  margin-bottom: 4px;
`;

const StatLabel = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
`;

const Plans = () => {
  const { user } = useAuth();
  const [plans, setPlans] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingPlan, setEditingPlan] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    descricao: '',
    preco: '',
    limite_usuarios: '',
    limite_documentos: '',
    limite_assinaturas: ''
  });
  const [filters, setFilters] = useState({
    search: ''
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
      key: 'descricao',
      title: 'Descrição',
      render: (value) => value || '-'
    },
    {
      key: 'preco',
      title: 'Preço',
      render: (value) => (
        <PriceTag>
          R$ {parseFloat(value).toFixed(2)}
        </PriceTag>
      )
    },
    {
      key: 'limites',
      title: 'Limites',
      render: (_, row) => (
        <div>
          <LimitBadge>{row.limite_usuarios} usuários</LimitBadge>
          <LimitBadge>{row.limite_documentos} docs</LimitBadge>
          <LimitBadge>{row.limite_assinaturas} assinaturas</LimitBadge>
        </div>
      )
    },
    {
      key: 'total_empresas',
      title: 'Empresas',
      render: (value) => value || 0
    },
    {
      key: 'actions',
      title: 'Ações',
      render: (_, row) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          {user?.tipo_usuario === 1 && (
            <>
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
            </>
          )}
        </div>
      )
    }
  ];

  useEffect(() => {
    fetchPlans();
    if (user?.tipo_usuario === 1) {
      fetchStats();
    }
  }, [filters, pagination.page]);

  const fetchPlans = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/plans?${params}`);
      setPlans(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar planos:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await api.get('/plans/stats');
      setStats(response.data.data);
    } catch (error) {
      console.error('Erro ao buscar estatísticas:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      const submitData = {
        ...formData,
        preco: parseFloat(formData.preco),
        limite_usuarios: parseInt(formData.limite_usuarios),
        limite_documentos: parseInt(formData.limite_documentos),
        limite_assinaturas: parseInt(formData.limite_assinaturas)
      };

      if (editingPlan) {
        await api.put(`/plans/${editingPlan.id}`, submitData);
      } else {
        await api.post('/plans', submitData);
      }
      
      setShowModal(false);
      setEditingPlan(null);
      resetForm();
      fetchPlans();
      if (user?.tipo_usuario === 1) {
        fetchStats();
      }
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(formatErrors(error.response.data.errors));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = (planToEdit) => {
    setEditingPlan(planToEdit);
    setFormData({
      nome: planToEdit.nome,
      descricao: planToEdit.descricao || '',
      preco: planToEdit.preco.toString(),
      limite_usuarios: planToEdit.limite_usuarios.toString(),
      limite_documentos: planToEdit.limite_documentos.toString(),
      limite_assinaturas: planToEdit.limite_assinaturas.toString()
    });
    setShowModal(true);
  };

  const handleDelete = async (planId) => {
    if (window.confirm('Tem certeza que deseja excluir este plano?')) {
      try {
        await api.delete(`/plans/${planId}`);
        fetchPlans();
        if (user?.tipo_usuario === 1) {
          fetchStats();
        }
      } catch (error) {
        console.error('Erro ao excluir plano:', error);
        if (error.response?.data?.message) {
          alert(error.response.data.message);
        }
      }
    }
  };

  const resetForm = () => {
    setFormData({
      nome: '',
      descricao: '',
      preco: '',
      limite_usuarios: '',
      limite_documentos: '',
      limite_assinaturas: ''
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

  const canManagePlans = user?.tipo_usuario === 1;

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Planos</PageTitle>
        {canManagePlans && (
          <Button onClick={() => setShowModal(true)}>
            Novo Plano
          </Button>
        )}
      </PageHeader>

      {stats && (
        <StatsGrid>
          <StatCard>
            <StatValue>{stats.geral.total_planos}</StatValue>
            <StatLabel>Total de Planos</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>R$ {parseFloat(stats.geral.preco_medio || 0).toFixed(2)}</StatValue>
            <StatLabel>Preço Médio</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>R$ {parseFloat(stats.geral.menor_preco || 0).toFixed(2)}</StatValue>
            <StatLabel>Menor Preço</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>R$ {parseFloat(stats.geral.maior_preco || 0).toFixed(2)}</StatValue>
            <StatLabel>Maior Preço</StatLabel>
          </StatCard>
        </StatsGrid>
      )}

      <FiltersContainer>
        <Input
          placeholder="Buscar por nome ou descrição..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={plans}
          loading={loading}
          emptyMessage="Nenhum plano encontrado"
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
          setEditingPlan(null);
          resetForm();
        }}
        title={editingPlan ? 'Editar Plano' : 'Novo Plano'}
      >
        <form onSubmit={handleSubmit}>
          <FormGrid>
            <Input
              label="Nome do Plano"
              value={formData.nome}
              onChange={(e) => setFormData(prev => ({ ...prev, nome: e.target.value }))}
              error={errors.nome?.[0]}
              required
            />
            
            <Input
              label="Preço (R$)"
              type="number"
              step="0.01"
              min="0"
              value={formData.preco}
              onChange={(e) => setFormData(prev => ({ ...prev, preco: e.target.value }))}
              error={errors.preco?.[0]}
              required
            />
            
            <Input
              label="Limite de Usuários"
              type="number"
              min="1"
              value={formData.limite_usuarios}
              onChange={(e) => setFormData(prev => ({ ...prev, limite_usuarios: e.target.value }))}
              error={errors.limite_usuarios?.[0]}
              required
            />
            
            <Input
              label="Limite de Documentos"
              type="number"
              min="1"
              value={formData.limite_documentos}
              onChange={(e) => setFormData(prev => ({ ...prev, limite_documentos: e.target.value }))}
              error={errors.limite_documentos?.[0]}
              required
            />
            
            <Input
              label="Limite de Assinaturas"
              type="number"
              min="1"
              value={formData.limite_assinaturas}
              onChange={(e) => setFormData(prev => ({ ...prev, limite_assinaturas: e.target.value }))}
              error={errors.limite_assinaturas?.[0]}
              required
            />
          </FormGrid>
          
          <FormRow>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Descrição
              </label>
              <textarea
                value={formData.descricao}
                onChange={(e) => setFormData(prev => ({ ...prev, descricao: e.target.value }))}
                rows={3}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  resize: 'vertical',
                  fontFamily: 'inherit'
                }}
                placeholder="Descrição do plano (opcional)"
              />
              {errors.descricao && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.descricao[0]}
                </span>
              )}
            </div>
          </FormRow>
          
          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              $variant="outline"
              onClick={() => {
                setShowModal(false);
                setEditingPlan(null);
                resetForm();
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingPlan ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
};

export default Plans;