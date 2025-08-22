import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import { toast } from 'react-toastify';
import Button from '../components/Button';
import Card from '../components/Card';
import Table from '../components/Table';
import Modal from '../components/Modal';
import Pagination from '../components/Pagination';
import {
  Search,
  Filter,
  ChevronLeft,
  ChevronRight,
  Eye,
  Edit,
  Check,
  X,
  Clock,
  AlertTriangle
} from 'lucide-react';

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

const PageSubtitle = styled.p`
  color: ${props => props.theme.colors.textSecondary};
  margin: 4px 0 0 0;
  font-size: 14px;
`;

const FiltersContainer = styled.div`
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
`;

const StatusBadge = styled.span`
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  
  ${props => {
    switch (props.status) {
      case 'pendente':
        return `
          background-color: #fef3c7;
          color: #92400e;
        `;
      case 'aprovada':
        return `
          background-color: #d1fae5;
          color: #059669;
        `;
      case 'rejeitada':
        return `
          background-color: #fee2e2;
          color: #dc2626;
        `;
      case 'processada':
        return `
          background-color: #dbeafe;
          color: #2563eb;
        `;
      default:
        return `
          background-color: #f3f4f6;
          color: #6b7280;
        `;
    }
  }}
`;

const Solicitacoes = () => {
  const { user } = useAuth();
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({
    current_page: 1,
    page_size: 10,
    total_items: 0,
    total_pages: 0
  });
  const [filters, setFilters] = useState({
    motivo: '',
    status: ''
  });
  const [editingRequest, setEditingRequest] = useState(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [editForm, setEditForm] = useState({
    status: '',
    observacoes_admin: ''
  });
  const [viewingRequest, setViewingRequest] = useState(null);
  const [showViewModal, setShowViewModal] = useState(false);

  const motivoOptions = [
    { value: '', label: 'Todos os motivos' },
    { value: 'inatividade', label: 'Inatividade do usuário' },
    { value: 'mudanca_empresa', label: 'Mudança de empresa' },
    { value: 'solicitacao_titular', label: 'Solicitação do titular dos dados' },
    { value: 'violacao_politica', label: 'Violação de política' },
    { value: 'outros', label: 'Outros motivos' }
  ];

  const statusOptions = [
    { value: '', label: 'Todos os status' },
    { value: 'pendente', label: 'Pendente' },
    { value: 'aprovada', label: 'Aprovada' },
    { value: 'rejeitada', label: 'Rejeitada' },
    { value: 'processada', label: 'Processada' }
  ];

  const getStatusBadge = (status) => {
    const statusConfig = {
      pendente: { color: 'bg-yellow-100 text-yellow-800', icon: Clock },
      aprovada: { color: 'bg-green-100 text-green-800', icon: Check },
      rejeitada: { color: 'bg-red-100 text-red-800', icon: X },
      processada: { color: 'bg-blue-100 text-blue-800', icon: Check }
    };

    const config = statusConfig[status] || { color: 'bg-gray-100 text-gray-800', icon: AlertTriangle };
    const Icon = config.icon;

    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color}`}>
        <Icon className="w-3 h-3 mr-1" />
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  const fetchRequests = async (page = 1) => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: page.toString(),
        page_size: pagination.page_size.toString(),
        ...filters
      });

      // Remove parâmetros vazios
      Object.keys(filters).forEach(key => {
        if (!filters[key]) {
          params.delete(key);
        }
      });

      const response = await api.get(`/users/deletion-requests?${params}`);
      
      if (response.data.success) {
        setRequests(response.data.data.requests);
        setPagination(response.data.data.pagination);
      } else {
        toast.error('Erro ao carregar solicitações');
      }
    } catch (error) {
      console.error('Erro ao carregar solicitações:', error);
      toast.error('Erro ao carregar solicitações');
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const applyFilters = () => {
    fetchRequests(1);
  };

  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= pagination.total_pages) {
      fetchRequests(newPage);
    }
  };

  const openEditModal = (request) => {
    setEditingRequest(request);
    setEditForm({
      status: request.status,
      observacoes_admin: request.observacoes_admin || ''
    });
    setShowEditModal(true);
  };

  const closeEditModal = () => {
    setShowEditModal(false);
    setEditingRequest(null);
    setEditForm({ status: '', observacoes_admin: '' });
  };

  const openViewModal = (request) => {
    setViewingRequest(request);
    setShowViewModal(true);
  };

  const closeViewModal = () => {
    setShowViewModal(false);
    setViewingRequest(null);
  };

  const handleUpdateRequest = async () => {
    try {
      const response = await api.post('/users/update-deletion-request', {
        id: editingRequest.id,
        ...editForm
      });

      if (response.data.success) {
        toast.success('Solicitação atualizada com sucesso!');
        closeEditModal();
        fetchRequests(pagination.current_page);
      } else {
        toast.error(response.data.message || 'Erro ao atualizar solicitação');
      }
    } catch (error) {
      console.error('Erro ao atualizar solicitação:', error);
      toast.error('Erro ao atualizar solicitação');
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('pt-BR');
  };

  const getMotiveLabel = (motivo) => {
    const option = motivoOptions.find(opt => opt.value === motivo);
    return option ? option.label : motivo;
  };

  useEffect(() => {
    fetchRequests();
  }, []);

  const canEdit = user?.tipo === 'super_admin';

  const columns = [
    {
      key: 'usuario_alvo_nome',
      label: 'Usuário Alvo',
      render: (value, row) => (
        <div>
          <div style={{ fontWeight: '500' }}>{row.usuario_alvo_nome}</div>
          <div style={{ fontSize: '12px', color: '#6b7280' }}>{row.usuario_alvo_email}</div>
        </div>
      )
    },
    {
      key: 'usuario_solicitante_nome',
      label: 'Solicitante',
      render: (value, row) => (
        <div>
          <div style={{ fontWeight: '500' }}>{row.usuario_solicitante_nome}</div>
          <div style={{ fontSize: '12px', color: '#6b7280' }}>{row.usuario_solicitante_email}</div>
        </div>
      )
    },
    {
      key: 'empresa_nome',
      label: 'Empresa'
    },
    {
      key: 'motivo',
      label: 'Motivo',
      render: (value) => getMotiveLabel(value)
    },
    {
      key: 'status',
      label: 'Status',
      render: (value) => (
        <StatusBadge status={value}>
          {getStatusIcon(value)}
          {value.charAt(0).toUpperCase() + value.slice(1)}
        </StatusBadge>
      )
    },
    {
      key: 'data_criacao',
      label: 'Data da Solicitação',
      render: (value) => formatDate(value)
    },
    {
      key: 'actions',
      label: 'Ações',
      render: (value, row) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button
            size="sm"
            $variant="outline"
            onClick={() => openViewModal(row)}
            title="Visualizar"
          >
            <Eye size={14} />
          </Button>
          {canEdit && row.status === 'pendente' && (
            <Button
              size="sm"
              $variant="outline"
              onClick={() => openEditModal(row)}
              title="Editar"
            >
              <Edit size={14} />
            </Button>
          )}
        </div>
      )
    }
  ];

  const getStatusIcon = (status) => {
    switch (status) {
      case 'pendente': return <Clock size={12} />;
      case 'aprovada': return <Check size={12} />;
      case 'rejeitada': return <X size={12} />;
      case 'processada': return <Check size={12} />;
      default: return <AlertTriangle size={12} />;
    }
  };

  return (
    <PageContainer>
      <PageHeader>
        <div>
          <PageTitle>Solicitações de Exclusão</PageTitle>
          <PageSubtitle>
            {canEdit 
              ? 'Gerencie todas as solicitações de exclusão de usuários do sistema'
              : 'Visualize as solicitações de exclusão da sua empresa'
            }
          </PageSubtitle>
        </div>
      </PageHeader>

      <FiltersContainer>
        <select
          value={filters.motivo}
          onChange={(e) => handleFilterChange('motivo', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px'
          }}
        >
          {motivoOptions.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>

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
          {statusOptions.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>

        <Button onClick={applyFilters}>
          Aplicar Filtros
        </Button>
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={requests}
          loading={loading}
          emptyMessage="Nenhuma solicitação encontrada"
        />
        {pagination.total_pages > 1 && (
          <Pagination
            currentPage={pagination.current_page}
            totalPages={pagination.total_pages}
            onPageChange={handlePageChange}
            totalItems={pagination.total_items}
            itemsPerPage={pagination.page_size}
          />
        )}
      </Card>

      {/* Modal de Visualização */}
        <Modal
          isOpen={showViewModal}
          onClose={closeViewModal}
          title="Detalhes da Solicitação"
          size="lg"
        >
          {viewingRequest && (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '16px' }}>
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Usuário Alvo
                </label>
                <p style={{ fontSize: '14px', margin: '0' }}>{viewingRequest.usuario_alvo_nome}</p>
                <p style={{ fontSize: '12px', color: '#6b7280', margin: '0' }}>{viewingRequest.usuario_alvo_email}</p>
              </div>
              
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Solicitante
                </label>
                <p style={{ fontSize: '14px', margin: '0' }}>{viewingRequest.usuario_solicitante_nome}</p>
                <p style={{ fontSize: '12px', color: '#6b7280', margin: '0' }}>{viewingRequest.usuario_solicitante_email}</p>
              </div>
              
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Empresa
                </label>
                <p style={{ fontSize: '14px', margin: '0' }}>{viewingRequest.empresa_nome}</p>
              </div>
              
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Motivo
                </label>
                <p style={{ fontSize: '14px', margin: '0' }}>{getMotiveLabel(viewingRequest.motivo)}</p>
              </div>
              
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Status
                </label>
                <StatusBadge status={viewingRequest.status}>
                  {getStatusIcon(viewingRequest.status)}
                  {viewingRequest.status.charAt(0).toUpperCase() + viewingRequest.status.slice(1)}
                </StatusBadge>
              </div>
              
              <div>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                  Data da Solicitação
                </label>
                <p style={{ fontSize: '14px', margin: '0' }}>{formatDate(viewingRequest.data_criacao)}</p>
              </div>
              
              {viewingRequest.detalhes && (
                <div style={{ gridColumn: '1 / -1' }}>
                  <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                    Detalhes
                  </label>
                  <p style={{ fontSize: '14px', backgroundColor: '#f9fafb', padding: '12px', borderRadius: '6px', margin: '0' }}>
                    {viewingRequest.detalhes}
                  </p>
                </div>
              )}
              
              {viewingRequest.observacoes_admin && (
                <div style={{ gridColumn: '1 / -1' }}>
                  <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                    Observações do Administrador
                  </label>
                  <p style={{ fontSize: '14px', backgroundColor: '#f9fafb', padding: '12px', borderRadius: '6px', margin: '0' }}>
                    {viewingRequest.observacoes_admin}
                  </p>
                </div>
              )}
            </div>
          )}
        </Modal>
 
        {/* Modal de Edição */}
        <Modal
          isOpen={showEditModal}
          onClose={closeEditModal}
          title="Editar Solicitação"
          size="md"
        >
          <form onSubmit={(e) => { e.preventDefault(); handleUpdateRequest(); }} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            <div>
              <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                Status
              </label>
              <select
                value={editForm.status}
                onChange={(e) => setEditForm(prev => ({ ...prev, status: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  outline: 'none'
                }}
              >
                <option value="pendente">Pendente</option>
                <option value="aprovada">Aprovada</option>
                <option value="rejeitada">Rejeitada</option>
                <option value="processada">Processada</option>
              </select>
            </div>
 
            <div>
              <label style={{ display: 'block', fontSize: '14px', fontWeight: '500', marginBottom: '4px' }}>
                Observações do Administrador
              </label>
              <textarea
                value={editForm.observacoes_admin}
                onChange={(e) => setEditForm(prev => ({ ...prev, observacoes_admin: e.target.value }))}
                rows={4}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  outline: 'none',
                  resize: 'vertical'
                }}
                placeholder="Adicione observações sobre esta solicitação..."
              />
            </div>
 
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '16px' }}>
              <Button
                type="button"
                variant="secondary"
                onClick={closeEditModal}
              >
                Cancelar
              </Button>
              <Button
                type="submit"
              >
                Salvar
              </Button>
            </div>
          </form>
        </Modal>
    </PageContainer>
  );
};

export default Solicitacoes;