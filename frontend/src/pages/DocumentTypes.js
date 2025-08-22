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
import { toast } from 'react-toastify';
import { FaPlus, FaEdit, FaTrash, FaTag } from 'react-icons/fa';

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
  display: flex;
  align-items: center;
  gap: 12px;
`;

const FiltersContainer = styled.div`
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
`;

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 16px;
`;

const StatusBadge = styled.span`
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background-color: ${props => props.active ? '#d1fae5' : '#fee2e2'};
  color: ${props => props.active ? '#065f46' : '#dc2626'};
`;

const DocumentTypes = () => {
  const { user } = useAuth();
  const [documentTypes, setDocumentTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingType, setEditingType] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    descricao: '',
    ativo: true
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
    { value: '1', label: 'Ativo' },
    { value: '0', label: 'Inativo' }
  ];

  const getStatusLabel = (active) => {
    return active ? 'Ativo' : 'Inativo';
  };

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
      key: 'ativo',
      title: 'Status',
      render: (value) => (
        <StatusBadge active={value}>
          {getStatusLabel(value)}
        </StatusBadge>
      )
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
          {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && (
            <>
              <Button
                size="sm"
                $variant="outline"
                onClick={() => handleEdit(row)}
              >
                <FaEdit /> Editar
              </Button>
              <Button
                size="sm"
                $variant="danger"
                onClick={() => handleDelete(row.id)}
              >
                <FaTrash /> Excluir
              </Button>
            </>
          )}
        </div>
      )
    }
  ];

  useEffect(() => {
    loadDocumentTypes();
  }, [pagination.page, pagination.pageSize, filters.search, filters.status]);

  const loadDocumentTypes = async () => {
    try {
      setLoading(true);
      const params = {
        page: pagination.page,
        limit: pagination.pageSize,
        search: filters.search || undefined,
        status: filters.status || undefined
      };

      const response = await api.get('/document-types', { params });
      
      if (response.data.success) {
        const responseData = response.data.data;
        setDocumentTypes(responseData.data || []);
        setPagination(prev => ({
          ...prev,
          total: responseData.total || 0
        }));
      } else {
        toast.error('Erro ao carregar tipos de documentos');
      }
    } catch (error) {
      console.error('Erro ao carregar tipos de documentos:', error);
      toast.error('Erro ao carregar tipos de documentos');
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = () => {
    setEditingType(null);
    setFormData({
      nome: '',
      descricao: '',
      ativo: true
    });
    setErrors({});
    setShowModal(true);
  };

  const handleEdit = (documentType) => {
    setEditingType(documentType);
    setFormData({
      nome: documentType.nome || '',
      descricao: documentType.descricao || '',
      ativo: documentType.ativo
    });
    setErrors({});
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      const url = editingType ? `/document-types/${editingType.id}` : '/document-types';
      const method = editingType ? 'put' : 'post';
      
      const response = await api[method](url, formData);
      
      if (response.data.success) {
        toast.success(editingType ? 'Tipo de documento atualizado com sucesso!' : 'Tipo de documento criado com sucesso!');
        setShowModal(false);
        loadDocumentTypes();
      } else {
        if (response.data.errors) {
          setErrors(formatErrors(response.data.errors));
        } else {
          toast.error(response.data.message || 'Erro ao salvar tipo de documento');
        }
      }
    } catch (error) {
      console.error('Erro ao salvar tipo de documento:', error);
      if (error.response?.data?.errors) {
        setErrors(formatErrors(error.response.data.errors));
      } else {
        toast.error('Erro ao salvar tipo de documento');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Tem certeza que deseja excluir este tipo de documento?')) {
      return;
    }

    try {
      const response = await api.delete(`/document-types/${id}`);
      
      if (response.data.success) {
        toast.success('Tipo de documento excluído com sucesso!');
        loadDocumentTypes();
      } else {
        toast.error(response.data.message || 'Erro ao excluir tipo de documento');
      }
    } catch (error) {
      console.error('Erro ao excluir tipo de documento:', error);
      toast.error('Erro ao excluir tipo de documento');
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({ ...prev, [name]: value }));
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, page }));
  };

  const handlePageSizeChange = (pageSize) => {
    setPagination(prev => ({ ...prev, pageSize, page: 1 }));
  };

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>
          <FaTag /> Classificação de Documentos
        </PageTitle>
        {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && (
          <Button onClick={handleCreate}>
            <FaPlus /> Novo Tipo
          </Button>
        )}
      </PageHeader>

      <FiltersContainer>
        <Input
          type="text"
          placeholder="Buscar por nome..."
          name="search"
          value={filters.search}
          onChange={handleFilterChange}
        />
        <select
          name="status"
          value={filters.status}
          onChange={handleFilterChange}
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
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={documentTypes}
          loading={loading}
          emptyMessage="Nenhum tipo de documento encontrado"
        />
        
        <Pagination
          currentPage={pagination.page}
          pageSize={pagination.pageSize}
          totalItems={pagination.total}
          totalPages={Math.ceil(pagination.total / pagination.pageSize)}
          onPageChange={handlePageChange}
          onPageSizeChange={handlePageSizeChange}
        />
      </Card>

      <Modal
        isOpen={showModal}
        title={editingType ? 'Editar Tipo de Documento' : 'Novo Tipo de Documento'}
        onClose={() => setShowModal(false)}
        size="medium"
      >
          <form onSubmit={handleSubmit}>
            <FormGrid>
              <Input
                label="Nome *"
                type="text"
                name="nome"
                value={formData.nome}
                onChange={handleInputChange}
                error={errors.nome}
                required
              />
              
              <Input
                label="Descrição"
                type="textarea"
                name="descricao"
                value={formData.descricao}
                onChange={handleInputChange}
                error={errors.descricao}
                rows={3}
              />
              
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <input
                  type="checkbox"
                  id="ativo"
                  name="ativo"
                  checked={formData.ativo}
                  onChange={handleInputChange}
                />
                <label htmlFor="ativo">Ativo</label>
              </div>
            </FormGrid>
            
            <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end', marginTop: '24px' }}>
              <Button
                type="button"
                $variant="outline"
                onClick={() => setShowModal(false)}
              >
                Cancelar
              </Button>
              <Button
                type="submit"
                disabled={submitting}
              >
                {submitting ? 'Salvando...' : (editingType ? 'Atualizar' : 'Criar')}
              </Button>
            </div>
          </form>
        </Modal>
    </PageContainer>
  );
};

export default DocumentTypes;