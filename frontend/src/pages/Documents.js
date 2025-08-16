import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
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
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background-color: ${props => {
    switch (props.status) {
      case 'rascunho': return '#fef3c7';
      case 'enviado': return '#dbeafe';
      case 'assinado': return '#d1fae5';
      case 'cancelado': return '#fee2e2';
      default: return '#f3f4f6';
    }
  }};
  color: ${props => {
    switch (props.status) {
      case 'rascunho': return '#92400e';
      case 'enviado': return '#1e40af';
      case 'assinado': return '#065f46';
      case 'cancelado': return '#dc2626';
      default: return '#374151';
    }
  }};
`;

const FileUploadArea = styled.div`
  border: 2px dashed ${props => props.theme.colors.border};
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s;
  
  &:hover {
    border-color: ${props => props.theme.colors.primary};
  }
  
  &.dragover {
    border-color: ${props => props.theme.colors.primary};
    background-color: ${props => props.theme.colors.background};
  }
`;

const FileInfo = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background-color: ${props => props.theme.colors.background};
  border-radius: 4px;
  margin-top: 8px;
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

const Documents = () => {
  const { user } = useAuth();
  const [documents, setDocuments] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingDocument, setEditingDocument] = useState(null);
  const [formData, setFormData] = useState({
    titulo: '',
    descricao: '',
    arquivo: null
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
  const [dragOver, setDragOver] = useState(false);

  const statusOptions = [
    { value: '', label: 'Todos os status' },
    { value: 'rascunho', label: 'Rascunho' },
    { value: 'enviado', label: 'Enviado' },
    { value: 'assinado', label: 'Assinado' },
    { value: 'cancelado', label: 'Cancelado' }
  ];

  const getStatusLabel = (status) => {
    const option = statusOptions.find(opt => opt.value === status);
    return option ? option.label : status;
  };

  const columns = [
    {
      key: 'titulo',
      title: 'Título',
      sortable: true
    },
    {
      key: 'descricao',
      title: 'Descrição',
      render: (value) => value || '-'
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
      key: 'nome_arquivo',
      title: 'Arquivo',
      render: (value) => value || '-'
    },
    {
      key: 'criado_por_nome',
      title: 'Criado por',
      render: (value) => value || '-'
    },
    {
      key: 'data_criacao',
      title: 'Data de Criação',
      render: (value) => new Date(value).toLocaleDateString('pt-BR')
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
    fetchDocuments();
    fetchStats();
  }, [filters, pagination.page]);

  const fetchDocuments = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        page_size: pagination.pageSize,
        ...filters
      });
      
      const response = await api.get(`/documents?${params}`);
      setDocuments(response.data.data?.items || []);
      setPagination(prev => ({
        ...prev,
        total: response.data.data?.pagination?.total || 0
      }));
    } catch (error) {
      console.error('Erro ao buscar documentos:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await api.get('/documents/stats');
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
      const submitData = new FormData();
      submitData.append('titulo', formData.titulo);
      submitData.append('descricao', formData.descricao);
      if (formData.arquivo) {
        submitData.append('arquivo', formData.arquivo);
      }

      if (editingDocument) {
        await api.put(`/documents/${editingDocument.id}`, submitData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
      } else {
        await api.post('/documents', submitData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
      }
      
      setShowModal(false);
      setEditingDocument(null);
      resetForm();
      fetchDocuments();
      fetchStats();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = (documentToEdit) => {
    setEditingDocument(documentToEdit);
    setFormData({
      titulo: documentToEdit.titulo,
      descricao: documentToEdit.descricao || '',
      arquivo: null
    });
    setShowModal(true);
  };

  const handleView = (document) => {
    if (document.caminho_arquivo) {
      // Abrir o documento em uma nova aba
      window.open(`${api.defaults.baseURL}/documents/${document.id}/download`, '_blank');
    }
  };

  const handleDelete = async (documentId) => {
    if (window.confirm('Tem certeza que deseja excluir este documento?')) {
      try {
        await api.delete(`/documents/${documentId}`);
        fetchDocuments();
        fetchStats();
      } catch (error) {
        console.error('Erro ao excluir documento:', error);
        if (error.response?.data?.message) {
          alert(error.response.data.message);
        }
      }
    }
  };

  const resetForm = () => {
    setFormData({
      titulo: '',
      descricao: '',
      arquivo: null
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

  const handleFileSelect = (file) => {
    setFormData(prev => ({ ...prev, arquivo: file }));
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setDragOver(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    setDragOver(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setDragOver(false);
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleFileSelect(files[0]);
    }
  };

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Documentos</PageTitle>
        <Button onClick={() => setShowModal(true)}>
          Novo Documento
        </Button>
      </PageHeader>

      {stats && (
        <StatsGrid>
          <StatCard>
            <StatValue>{stats.geral.total_documentos}</StatValue>
            <StatLabel>Total de Documentos</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.rascunho || 0}</StatValue>
            <StatLabel>Rascunhos</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.enviado || 0}</StatValue>
            <StatLabel>Enviados</StatLabel>
          </StatCard>
          <StatCard>
            <StatValue>{stats.por_status.assinado || 0}</StatValue>
            <StatLabel>Assinados</StatLabel>
          </StatCard>
        </StatsGrid>
      )}

      <FiltersContainer>
        <Input
          placeholder="Buscar por título ou descrição..."
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
          data={documents}
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
          setEditingDocument(null);
          resetForm();
        }}
        title={editingDocument ? 'Editar Documento' : 'Novo Documento'}
      >
        <form onSubmit={handleSubmit}>
          <FormGrid>
            <Input
              label="Título"
              value={formData.titulo}
              onChange={(e) => setFormData(prev => ({ ...prev, titulo: e.target.value }))}
              error={errors.titulo?.[0]}
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
                placeholder="Descrição do documento (opcional)"
              />
              {errors.descricao && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.descricao[0]}
                </span>
              )}
            </div>
          </FormRow>

          <FormRow>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', fontWeight: '500' }}>
                Arquivo {!editingDocument && '*'}
              </label>
              <FileUploadArea
                className={dragOver ? 'dragover' : ''}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => document.getElementById('file-input').click()}
              >
                <input
                  id="file-input"
                  type="file"
                  accept=".pdf,.doc,.docx,.txt"
                  onChange={(e) => handleFileSelect(e.target.files[0])}
                  style={{ display: 'none' }}
                />
                {formData.arquivo ? (
                  <FileInfo>
                    <span>📄</span>
                    <span>{formData.arquivo.name}</span>
                    <Button
                      type="button"
                      size="sm"
                      $variant="outline"
                      onClick={(e) => {
                        e.stopPropagation();
                        setFormData(prev => ({ ...prev, arquivo: null }));
                      }}
                    >
                      Remover
                    </Button>
                  </FileInfo>
                ) : (
                  <div>
                    <p>Clique aqui ou arraste um arquivo</p>
                    <p style={{ fontSize: '12px', color: '#6b7280' }}>
                      Formatos aceitos: PDF, DOC, DOCX, TXT
                    </p>
                  </div>
                )}
              </FileUploadArea>
              {errors.arquivo && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.arquivo[0]}
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
                setEditingDocument(null);
                resetForm();
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingDocument ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>
    </PageContainer>
  );
};

export default Documents;