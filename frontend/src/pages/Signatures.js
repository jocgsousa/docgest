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

const StatusBadge = styled.span`
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background-color: ${props => {
    switch (props.status) {
      case 'pendente': return '#fef3c7';
      case 'assinado': return '#d1fae5';
      case 'rejeitado': return '#fee2e2';
      case 'expirado': return '#f3f4f6';
      default: return '#f3f4f6';
    }
  }};
  color: ${props => {
    switch (props.status) {
      case 'pendente': return '#92400e';
      case 'assinado': return '#065f46';
      case 'rejeitado': return '#dc2626';
      case 'expirado': return '#374151';
      default: return '#374151';
    }
  }};
`;

const SignersList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
`;

const SignerItem = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px;
  background-color: ${props => props.theme.colors.background};
  border-radius: 4px;
  border: 1px solid ${props => props.theme.colors.border};
`;

const SignerInfo = styled.div`
  display: flex;
  flex-direction: column;
  gap: 2px;
`;

const SignerName = styled.span`
  font-weight: 500;
  font-size: 14px;
`;

const SignerEmail = styled.span`
  font-size: 12px;
  color: ${props => props.theme.colors.textSecondary};
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

const Signatures = () => {
  const { user } = useAuth();
  const [signatures, setSignatures] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingSignature, setEditingSignature] = useState(null);
  const [formData, setFormData] = useState({
    documento_id: '',
    signatarios: [{ nome: '', email: '', ordem: 1 }]
  });
  const [filters, setFilters] = useState({
    search: '',
    status: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const statusOptions = [
    { value: '', label: 'Todos os status' },
    { value: 'pendente', label: 'Pendente' },
    { value: 'assinado', label: 'Assinado' },
    { value: 'rejeitado', label: 'Rejeitado' },
    { value: 'expirado', label: 'Expirado' }
  ];

  const getStatusLabel = (status) => {
    const option = statusOptions.find(opt => opt.value === status);
    return option ? option.label : status;
  };

  const columns = [
    {
      key: 'documento_titulo',
      title: 'Documento',
      sortable: true
    },
    {
      key: 'status',
      title: 'Status',
      render: (value) => (
        <StatusBadge status={value}>
          {getStatusLabel(value)}
        </StatusBadge>
      )
    },
    {
      key: 'signatarios',
      title: 'Signatários',
      render: (_, row) => (
        <div>
          {row.signatarios && row.signatarios.length > 0 ? (
            <div>
              <div>{row.signatarios.length} signatário(s)</div>
              <div style={{ fontSize: '12px', color: '#6b7280' }}>
                {row.signatarios.filter(s => s.status === 'assinado').length} assinado(s)
              </div>
            </div>
          ) : (
            '-'
          )}
        </div>
      )
    },
    {
      key: 'data_criacao',
      title: 'Data de Criação',
      render: (value) => new Date(value).toLocaleDateString('pt-BR')
    },
    {
      key: 'data_expiracao',
      title: 'Data de Expiração',
      render: (value) => value ? new Date(value).toLocaleDateString('pt-BR') : '-'
    },
    {
      key: 'actions',
      title: 'Ações',
      render: (_, row) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button
            size="sm"
            $variant="outline"
            onClick={() => handleView(row)}
          >
            Ver
          </Button>
          {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2 || row.criado_por === user?.id) && (
            <>
              {row.status === 'pendente' && (
                <Button
                  size="sm"
                  $variant="outline"
                  onClick={() => handleSendReminder(row.id)}
                >
                  Lembrete
                </Button>
              )}
              <Button
                size="sm"
                $variant="danger"
                onClick={() => handleCancel(row.id)}
                disabled={row.status === 'assinado'}
              >
                Cancelar
              </Button>
            </>
          )}
        </div>
      )
    }
  ];

  useEffect(() => {
    fetchSignatures();
    fetchDocuments();
    fetchStats();
  }, [filters, pagination.page]);

  const fetchSignatures = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/signatures?${params}`);
      setSignatures(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar assinaturas:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchDocuments = async () => {
    try {
      const response = await api.get('/documents/select');
      setDocuments(response.data.data);
    } catch (error) {
      console.error('Erro ao buscar documentos:', error);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await api.get('/signatures/stats');
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
        documento_id: parseInt(formData.documento_id),
        signatarios: (formData.signatarios && formData.signatarios.map((sig, index) => ({
          ...sig,
          ordem: index + 1
        }))) || []
      };

      await api.post('/signatures', submitData);
      
      setShowModal(false);
      resetForm();
      fetchSignatures();
      fetchStats();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(formatErrors(error.response.data.errors));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleView = (signature) => {
    // Implementar visualização detalhada da assinatura
    console.log('Ver assinatura:', signature);
  };

  const handleSendReminder = async (signatureId) => {
    try {
      await api.post(`/signatures/${signatureId}/reminder`);
      alert('Lembrete enviado com sucesso!');
    } catch (error) {
      console.error('Erro ao enviar lembrete:', error);
      alert('Erro ao enviar lembrete');
    }
  };

  const handleCancel = async (signatureId) => {
    if (window.confirm('Tem certeza que deseja cancelar esta assinatura?')) {
      try {
        await api.put(`/signatures/${signatureId}/cancel`);
        fetchSignatures();
        fetchStats();
      } catch (error) {
        console.error('Erro ao cancelar assinatura:', error);
        if (error.response?.data?.message) {
          alert(error.response.data.message);
        }
      }
    }
  };

  const resetForm = () => {
    setFormData({
      documento_id: '',
      signatarios: [{ nome: '', email: '', ordem: 1 }]
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

  const addSigner = () => {
    setFormData(prev => ({
      ...prev,
      signatarios: [...prev.signatarios, { nome: '', email: '', ordem: prev.signatarios.length + 1 }]
    }));
  };

  const removeSigner = (index) => {
    setFormData(prev => ({
      ...prev,
      signatarios: prev.signatarios.filter((_, i) => i !== index)
    }));
  };

  const updateSigner = (index, field, value) => {
    setFormData(prev => ({
      ...prev,
      signatarios: (prev.signatarios && prev.signatarios.map((signer, i) => 
        i === index ? { ...signer, [field]: value } : signer
      )) || []
    }));
  };

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Assinaturas</PageTitle>
        <Button onClick={() => setShowModal(true)}>
          Nova Assinatura
        </Button>
      </PageHeader>

      {stats && (
        <StatsGrid>
          <StatCard>
            <StatValue>{stats.geral.total_assinaturas}</StatValue>
            <StatLabel>Total de Assinaturas</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.pendente || 0}</StatValue>
            <StatLabel>Pendentes</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.assinado || 0}</StatValue>
            <StatLabel>Assinadas</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.rejeitado || 0}</StatValue>
            <StatLabel>Rejeitadas</StatLabel>
          </StatCard>
        </StatsGrid>
      )}

      <FiltersContainer>
        <Input
          placeholder="Buscar por documento..."
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
            fontSize: '14px',
            minWidth: '150px'
          }}
        >
          {statusOptions.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={signatures}
          loading={loading}
        />
      </Card>

      <Pagination
        currentPage={pagination.page}
        totalPages={Math.ceil(pagination.total / pagination.pageSize)}
        totalItems={pagination.total}
        pageSize={pagination.pageSize}
        onPageChange={(page) => setPagination(prev => ({ ...prev, page }))}
        onPageSizeChange={(pageSize) => setPagination(prev => ({ ...prev, pageSize, page: 1 }))}
        pageSizeOptions={[10, 25, 50, 100]}
      />

      <Modal
        isOpen={showModal}
        onClose={() => {
          setShowModal(false);
          resetForm();
        }}
        title="Nova Assinatura"
        size="large"
      >
        <form onSubmit={handleSubmit}>
          <FormRow>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Documento *
              </label>
              <select
                value={formData.documento_id}
                onChange={(e) => setFormData(prev => ({ ...prev, documento_id: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
                required
              >
                <option value="">Selecione um documento</option>
                {documents && documents.map(doc => (
                  <option key={doc.id} value={doc.id}>
                    {doc.titulo}
                  </option>
                ))}
              </select>
              {errors.documento_id && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.documento_id[0]}
                </span>
              )}
            </div>
          </FormRow>

          <FormRow>
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                <label style={{ fontSize: '14px', fontWeight: '500' }}>
                  Signatários *
                </label>
                <Button
                  type="button"
                  size="sm"
                  $variant="outline"
                  onClick={addSigner}
                >
                  Adicionar Signatário
                </Button>
              </div>
              
              <SignersList>
                {formData.signatarios && formData.signatarios.map((signer, index) => (
                  <SignerItem key={index}>
                    <SignerInfo>
                      <div style={{ display: 'flex', gap: '8px', marginBottom: '4px' }}>
                        <Input
                          placeholder="Nome do signatário"
                          value={signer.nome}
                          onChange={(e) => updateSigner(index, 'nome', e.target.value)}
                          style={{ flex: 1 }}
                          required
                        />
                        <Input
                          type="email"
                          placeholder="Email do signatário"
                          value={signer.email}
                          onChange={(e) => updateSigner(index, 'email', e.target.value)}
                          style={{ flex: 1 }}
                          required
                        />
                      </div>
                      <div style={{ fontSize: '12px', color: '#6b7280' }}>
                        Ordem de assinatura: {index + 1}
                      </div>
                    </SignerInfo>
                    {formData.signatarios.length > 1 && (
                      <Button
                        type="button"
                        size="sm"
                        $variant="danger"
                        onClick={() => removeSigner(index)}
                      >
                        Remover
                      </Button>
                    )}
                  </SignerItem>
                ))}
              </SignersList>
              
              {errors.signatarios && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.signatarios[0]}
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
                resetForm();
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              Criar Assinatura
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
};

export default Signatures;