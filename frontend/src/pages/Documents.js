import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import { useData } from '../contexts/DataContext';
import api, { publicApi } from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Table from '../components/Table';
import Modal from '../components/Modal';
import Pagination from '../components/Pagination';
import { formatErrors } from '../utils/fieldLabels';
import { toast } from 'react-toastify';
import { FaPlus, FaEdit, FaTrash, FaEye, FaUpload, FaFilePdf, FaFileWord, FaFileExcel, FaFileImage, FaFile, FaTimes, FaDownload } from 'react-icons/fa';

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
      case 'arquivo': return '#f3e8ff';
      default: return '#f3f4f6';
    }
  }};
  color: ${props => {
    switch (props.status) {
      case 'rascunho': return '#92400e';
      case 'enviado': return '#1e40af';
      case 'assinado': return '#065f46';
      case 'cancelado': return '#dc2626';
      case 'arquivo': return '#7c3aed';
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



const ViewModal = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
`;

const ViewModalContent = styled.div`
  background: white;
  border-radius: 8px;
  width: 95vw;
  height: 90vh;
  display: flex;
  flex-direction: column;
  position: relative;
`;

const ViewModalHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid ${props => props.theme.colors.border};
  background: ${props => props.theme.colors.background};
  border-radius: 8px 8px 0 0;
`;

const ViewModalTitle = styled.h3`
  margin: 0;
  flex: 1;
  color: ${props => props.theme.colors.text};
`;

const ViewModalActions = styled.div`
  display: flex;
  gap: 8px;
`;

const ViewModalBody = styled.div`
  flex: 1;
  padding: 20px;
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
`;

const TextContent = styled.div`
  width: 100%;
  height: 100%;
  padding: 20px;
  font-family: 'Courier New', monospace;
  font-size: 14px;
  line-height: 1.5;
  white-space: pre-wrap;
  background: #f8f9fa;
  border-radius: 4px;
  overflow: auto;
`;

const PdfViewer = styled.iframe`
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 4px;
`;

const DownloadMessage = styled.div`
  text-align: center;
  padding: 40px;
  color: ${props => props.theme.colors.textSecondary};
  
  h3 {
    margin-bottom: 16px;
    color: ${props => props.theme.colors.text};
  }
  
  p {
    margin-bottom: 24px;
  }
`;

const Documents = ({ openCreateModal = false }) => {
  const { user } = useAuth();
  const [documents, setDocuments] = useState([]);

  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(openCreateModal);
  const [editingDocument, setEditingDocument] = useState(null);
  const [formData, setFormData] = useState({ 
    titulo: '', 
    descricao: '', 
    arquivo: null, 
    empresa_id: '', 
    filial_id: '', 
    assinantes: [],
    status: 'rascunho',
    tipo_documento_id: '',
    prazo_assinatura: '',
    competencia: '',
    validade_legal: ''
  });
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    tipo_documento_id: ''
  });
  const [pagination, setPagination] = useState({
    page: 1,
    pageSize: 10,
    total: 0
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [companies, setCompanies] = useState([]);
  const [uploadConfig, setUploadConfig] = useState({
    allowed_file_types: 'pdf,doc,docx,txt',
    max_file_size: 10,
    max_file_size_bytes: 10 * 1024 * 1024
  });
  const [branches, setBranches] = useState([]);
  const [availableUsers, setAvailableUsers] = useState([]);
  const [loadingUsers, setLoadingUsers] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [viewingDocument, setViewingDocument] = useState(null);
  const [documentContent, setDocumentContent] = useState(null);
  const [loadingContent, setLoadingContent] = useState(false);
  const [documentTypes, setDocumentTypes] = useState([]);

  const statusOptions = [
    { value: '', label: 'Todos os status' },
    { value: 'rascunho', label: 'Rascunho' },
    { value: 'enviado', label: 'Enviado' },
    { value: 'assinado', label: 'Assinado' },
    { value: 'cancelado', label: 'Cancelado' },
    { value: 'arquivo', label: 'Arquivo' }
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
  }, [filters, pagination.page]);

  useEffect(() => {
    loadCompanies();
    loadUploadConfig();
    loadDocumentTypes();
  }, []);

  // Inicializar campos para usuários assinantes
  useEffect(() => {
    if (user && user.tipo_usuario === 3 && user.empresa_id) {
      setFormData(prev => ({
        ...prev,
        empresa_id: user.empresa_id.toString(),
        filial_id: user.filial_id ? user.filial_id.toString() : ''
      }));
    }
  }, [user, companies]);

  const loadUploadConfig = async () => {
    try {
      const config = await publicApi.getUploadConfig();
      setUploadConfig(config);
    } catch (error) {
      console.error('Erro ao carregar configurações de upload:', error);
      // Mantém os valores padrão em caso de erro
    }
  };

  useEffect(() => {
    if (formData.empresa_id) {
      loadBranches(formData.empresa_id);
      loadUsers(formData.empresa_id, formData.filial_id);
    } else {
      setBranches([]);
      setAvailableUsers([]);
    }
  }, [formData.empresa_id]);

  useEffect(() => {
    if (formData.empresa_id && formData.filial_id) {
      loadUsers(formData.empresa_id, formData.filial_id);
    }
  }, [formData.filial_id]);

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

  // Carregar empresas
  const loadCompanies = async () => {
    try {
      const response = await api.get('/companies/for-users');
      if (response.data.success) {
        setCompanies(Array.isArray(response.data.data) ? response.data.data : []);
      }
    } catch (error) {
      console.error('Erro ao carregar empresas:', error);
      setCompanies([]);
    }
  };

  // Carregar filiais por empresa
  const loadBranches = async (empresaId) => {
    try {
      const response = await api.get(`/branches/for-users?empresa_id=${empresaId}`);
      if (response.data.success) {
        setBranches(Array.isArray(response.data.data) ? response.data.data : []);
      }
    } catch (error) {
      console.error('Erro ao carregar filiais:', error);
      setBranches([]);
    }
  };

  // Carregar usuários por empresa e filial
  const loadUsers = async (empresaId, filialId) => {
    if (!empresaId) return;
    
    try {
      setLoadingUsers(true);
      const params = { empresa_id: empresaId };
      if (filialId) params.filial_id = filialId;
      
      const response = await api.get('/users/by-company-branch', { params });
      if (response.data.success) {
        setAvailableUsers(Array.isArray(response.data.data) ? response.data.data : []);
      }
    } catch (error) {
      console.error('Erro ao carregar usuários:', error);
      setAvailableUsers([]);
    } finally {
      setLoadingUsers(false);
    }
  };

  // Carregar tipos de documentos
  const loadDocumentTypes = async () => {
    try {
      const response = await api.get('/document-types');
      if (response.data.success) {
        setDocumentTypes(Array.isArray(response.data.data.data) ? response.data.data.data : []);
      }
    } catch (error) {
      console.error('Erro ao carregar tipos de documentos:', error);
      setDocumentTypes([]);
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
      if (formData.empresa_id) {
        submitData.append('empresa_id', formData.empresa_id);
      }
      if (formData.filial_id) {
        submitData.append('filial_id', formData.filial_id);
      }
      if (formData.assinantes && formData.assinantes.length > 0) {
        submitData.append('assinantes', JSON.stringify(formData.assinantes));
      }
      if (formData.status) {
        submitData.append('status', formData.status);
      }
      if (formData.tipo_documento_id) {
        submitData.append('tipo_documento_id', formData.tipo_documento_id);
      }
      if (formData.prazo_assinatura) {
        submitData.append('prazo_assinatura', formData.prazo_assinatura);
      }
      if (formData.competencia) {
        submitData.append('competencia', formData.competencia);
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
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(formatErrors(error.response.data.errors));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = async (documentToEdit) => {
    try {
      // Buscar dados completos do documento
      const response = await api.get(`/documents/${documentToEdit.id}`);
      const fullDocument = response.data.data;
      
      setEditingDocument(fullDocument);
      
      // Carregar empresas se necessário
      if (companies.length === 0) {
        await loadCompanies();
      }
      
      // Carregar filiais se há empresa selecionada
      if (fullDocument.empresa_id) {
        await loadBranches(fullDocument.empresa_id);
      }
      
      // Carregar usuários se há empresa selecionada
      if (fullDocument.empresa_id) {
        await loadUsers(fullDocument.empresa_id, fullDocument.filial_id);
      }
      
      // Extrair IDs dos assinantes
      const assinantesIds = fullDocument.assinantes ? fullDocument.assinantes.map(a => a.usuario_id) : [];
      
      setFormData({
        titulo: fullDocument.titulo,
        descricao: fullDocument.descricao || '',
        arquivo: null,
        empresa_id: fullDocument.empresa_id || '',
        filial_id: fullDocument.filial_id || '',
        assinantes: assinantesIds,
        status: fullDocument.status || 'rascunho',
        tipo_documento_id: fullDocument.tipo_documento_id || '',
        prazo_assinatura: fullDocument.prazo_assinatura || '',
        competencia: fullDocument.competencia || '',
        validade_legal: fullDocument.validade_legal || ''
      });
      
      setShowModal(true);
    } catch (error) {
      console.error('Erro ao carregar dados do documento:', error);
      alert('Erro ao carregar dados do documento');
    }
  };

  const handleView = async (document) => {
    if (!document.caminho_arquivo) {
      toast.error('Documento não possui arquivo anexado');
      return;
    }

    setViewingDocument(document);
    setShowViewModal(true);
    setLoadingContent(true);
    setDocumentContent(null);

    try {
      const fileExtension = document.nome_arquivo.split('.').pop().toLowerCase();
      
      // Para arquivos que devem ser baixados (DOCX, planilhas)
      if (['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt'].includes(fileExtension)) {
        setDocumentContent({ type: 'download', extension: fileExtension });
        setLoadingContent(false);
        return;
      }
      
      // Para arquivos de texto, buscar o conteúdo via API
      if (fileExtension === 'txt') {
        const response = await api.get(`/documents/${document.id}/view`);
        setDocumentContent(response.data.data);
      } else {
        // Para PDFs e outros arquivos, buscar como blob com autenticação
        const response = await api.get(`/documents/${document.id}/view`, {
          responseType: 'blob'
        });
        
        // Criar URL local para o blob
        const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/pdf' });
        const blobUrl = URL.createObjectURL(blob);
        
        setDocumentContent({ 
          type: 'file', 
          url: blobUrl,
          extension: fileExtension 
        });
      }
    } catch (error) {
      console.error('Erro ao carregar documento:', error);
      toast.error('Erro ao carregar documento');
      setShowViewModal(false);
    } finally {
      setLoadingContent(false);
    }
  };

  const handleCloseViewModal = () => {
    // Limpar URL do blob se existir
    if (documentContent && documentContent.type === 'file' && documentContent.url && documentContent.url.startsWith('blob:')) {
      URL.revokeObjectURL(documentContent.url);
    }
    
    setShowViewModal(false);
    setViewingDocument(null);
    setDocumentContent(null);
  };

  const handleDownloadDocument = async () => {
    if (viewingDocument && viewingDocument.hash_acesso) {
      try {
        const token = localStorage.getItem('token');
        if (!token) {
           toast.error('Token de autenticação não encontrado');
           return;
         }

        // Criar um link temporário para download com autenticação
        const response = await api.get(`/documents/${viewingDocument.hash_acesso}/download`, {
          responseType: 'blob',
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        // Criar URL do blob e fazer download
        const blob = new Blob([response.data]);
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = viewingDocument.nome_arquivo || 'documento';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
         console.error('Erro ao baixar documento:', error);
         toast.error('Erro ao baixar documento: ' + (error.response?.data?.message || error.message));
       }
    } else {
       toast.error('Hash de acesso do documento não encontrado');
     }
  };

  const renderDocumentContent = () => {
    if (loadingContent) {
      return (
        <div style={{ textAlign: 'center', padding: '40px' }}>
          <p>Carregando documento...</p>
        </div>
      );
    }

    if (!documentContent) {
      return (
        <div style={{ textAlign: 'center', padding: '40px' }}>
          <p>Erro ao carregar documento</p>
        </div>
      );
    }

    // Conteúdo de texto
    if (documentContent.type === 'text') {
      return (
        <TextContent>
          {documentContent.content}
        </TextContent>
      );
    }

    // Arquivos para download
    if (documentContent.type === 'download') {
      return (
        <DownloadMessage>
          <h3>Arquivo {documentContent.extension.toUpperCase()}</h3>
          <p>Este tipo de arquivo precisa ser baixado para visualização.</p>
          <Button onClick={handleDownloadDocument}>
            <FaDownload style={{ marginRight: '8px' }} />
            Baixar Arquivo
          </Button>
        </DownloadMessage>
      );
    }

    // PDFs e outros arquivos
    if (documentContent.type === 'file') {
      if (documentContent.extension === 'pdf') {
        return (
          <PdfViewer
            src={documentContent.url}
            title={viewingDocument?.nome_arquivo}
          />
        );
      } else {
        // Para outros tipos de arquivo, mostrar opção de download
        return (
          <DownloadMessage>
            <h3>Arquivo {documentContent.extension.toUpperCase()}</h3>
            <p>Visualização não disponível para este tipo de arquivo.</p>
            <Button onClick={handleDownloadDocument}>
              <FaDownload style={{ marginRight: '8px' }} />
              Baixar Arquivo
            </Button>
          </DownloadMessage>
        );
      }
    }

    return null;
  };

  const handleDelete = async (documentId) => {
    if (window.confirm('Tem certeza que deseja excluir este documento?')) {
      try {
        await api.delete(`/documents/${documentId}`);
        fetchDocuments();
      } catch (error) {
        console.error('Erro ao excluir documento:', error);
        if (error.response?.data?.message) {
          alert(error.response.data.message);
        }
      }
    }
  };

  const resetForm = () => {
    const baseFormData = { 
      titulo: '', 
      descricao: '', 
      arquivo: null, 
      empresa_id: '', 
      filial_id: '', 
      assinantes: [],
      status: 'rascunho',
      tipo_documento_id: '',
      prazo_assinatura: '',
      competencia: '',
      validade_legal: ''
    };
    
    // Para usuários assinantes, manter empresa_id e filial_id preenchidos
    if (user && user.tipo_usuario === 3 && user.empresa_id) {
      baseFormData.empresa_id = user.empresa_id.toString();
      baseFormData.filial_id = user.filial_id ? user.filial_id.toString() : '';
    }
    
    setFormData(baseFormData);
    setErrors({});
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }));
  };

  const applyFilters = () => {
    setPagination(prev => ({ ...prev, page: 1 }));
    fetchDocuments();
  };

  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, page }));
  };

  const handleFileSelect = (file) => {
    if (!file) {
      // Limpar arquivo e erros se nenhum arquivo foi selecionado
      setFormData(prev => ({ ...prev, arquivo: null }));
      setErrors(prev => ({ ...prev, arquivo: null }));
      return;
    }
    
    // Limpar erros anteriores imediatamente
    setErrors(prev => ({ ...prev, arquivo: null }));
    
    // Validar tipo do arquivo primeiro
    const fileExtension = file.name.split('.').pop().toLowerCase();
    const allowedTypes = uploadConfig.allowed_file_types ? 
      uploadConfig.allowed_file_types.split(',').map(type => type.trim().toLowerCase()) :
      ['pdf', 'doc', 'docx', 'txt'];
    
    if (!allowedTypes.includes(fileExtension)) {
      setErrors(prev => ({
        ...prev,
        arquivo: [`Tipo de arquivo não permitido. Tipos aceitos: ${allowedTypes.join(', ').toUpperCase()}`]
      }));
      // Não definir o arquivo se o tipo for inválido
      setFormData(prev => ({ ...prev, arquivo: null }));
      return;
    }
    
    // Validar tamanho do arquivo
    const maxSizeBytes = uploadConfig.max_file_size_bytes || (10 * 1024 * 1024); // 10MB default
    const maxSizeMB = uploadConfig.max_file_size || 10;
    
    if (file.size > maxSizeBytes) {
      const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
      setErrors(prev => ({
        ...prev,
        arquivo: [`Arquivo muito grande (${fileSizeMB}MB). Tamanho máximo permitido: ${maxSizeMB}MB`]
      }));
      // Não definir o arquivo se o tamanho for inválido
      setFormData(prev => ({ ...prev, arquivo: null }));
      return;
    }
    
    // Se chegou até aqui, o arquivo é válido
    setFormData(prev => ({ ...prev, arquivo: file }));
    
    // Mostrar feedback positivo (opcional)
    console.log(`Arquivo válido selecionado: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)}MB)`);
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
        <select
          value={filters.tipo_documento_id}
          onChange={(e) => handleFilterChange('tipo_documento_id', e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            fontSize: '14px',
            minWidth: '150px'
          }}
        >
          <option value="">Todas as classificações</option>
          {Array.isArray(documentTypes) && documentTypes.map(type => (
            <option key={type.id} value={type.id}>
              {type.nome}
            </option>
          ))}
        </select>
        <Button onClick={applyFilters}>
          Buscar
        </Button>
      </FiltersContainer>

      <Card>
        <Table
          columns={columns}
          data={documents}
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

          <FormGrid>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Empresa *
              </label>
              <select
                value={formData.empresa_id}
                onChange={(e) => {
                  setFormData(prev => ({ 
                    ...prev, 
                    empresa_id: e.target.value,
                    filial_id: '',
                    assinantes: []
                  }));
                }}
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
                {Array.isArray(companies) && companies.map(company => (
                  <option key={company.id} value={company.id}>
                    {company.nome}
                  </option>
                ))}
              </select>
              {errors.empresa_id && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.empresa_id}
                </span>
              )}
            </div>

            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Filial
              </label>
              <select
                value={formData.filial_id}
                onChange={(e) => {
                  setFormData(prev => ({ 
                    ...prev, 
                    filial_id: e.target.value,
                    assinantes: []
                  }));
                }}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
                disabled={!formData.empresa_id || branches.length === 0}
              >
                <option value="">Todas as filiais</option>
                {Array.isArray(branches) && branches.map(branch => (
                  <option key={branch.id} value={branch.id}>
                    {branch.nome}
                  </option>
                ))}
              </select>
            </div>
          </FormGrid>

          <FormGrid>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Classificação
              </label>
              <select
                value={formData.tipo_documento_id}
                onChange={(e) => setFormData(prev => ({ ...prev, tipo_documento_id: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
              >
                <option value="">Selecione um tipo</option>
                {Array.isArray(documentTypes) && documentTypes.map(type => (
                  <option key={type.id} value={type.id}>
                    {type.nome}
                  </option>
                ))}
              </select>
              {errors.tipo_documento_id && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.tipo_documento_id[0]}
                </span>
              )}
            </div>
          </FormGrid>

          <FormGrid>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Competência
              </label>
              <input
                type="month"
                value={formData.competencia}
                onChange={(e) => setFormData(prev => ({ ...prev, competencia: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
              />
              {errors.competencia && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.competencia[0]}
                </span>
              )}
            </div>

            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                Validade Legal
              </label>
              <input
                type="date"
                value={formData.validade_legal}
                onChange={(e) => setFormData(prev => ({ ...prev, validade_legal: e.target.value }))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
              />
              {errors.validade_legal && (
                <span style={{ color: '#dc2626', fontSize: '12px' }}>
                  {errors.validade_legal[0]}
                </span>
              )}
            </div>
          </FormGrid>

          {/* Campo de Assinantes - apenas para Admin e Administrador de Empresa */}
          {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && (
            <FormRow>
              <div>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Assinantes
                </label>
                <div style={{ 
                  border: '1px solid #d1d5db', 
                  borderRadius: '6px', 
                  maxHeight: '150px', 
                  overflowY: 'auto',
                  padding: '8px'
                }}>
                  {loadingUsers ? (
                    <div style={{ padding: '16px', textAlign: 'center', color: '#6b7280' }}>
                      Carregando usuários...
                    </div>
                  ) : availableUsers.length === 0 ? (
                    <div style={{ padding: '16px', textAlign: 'center', color: '#6b7280' }}>
                      {formData.empresa_id ? 'Nenhum usuário encontrado' : 'Selecione uma empresa primeiro'}
                    </div>
                  ) : (
                    Array.isArray(availableUsers) && availableUsers.map(user => (
                      <label key={user.id} style={{ 
                        display: 'flex', 
                        alignItems: 'center', 
                        gap: '8px', 
                        padding: '4px 0',
                        cursor: 'pointer'
                      }}>
                        <input
                          type="checkbox"
                          checked={formData.assinantes.includes(user.id)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setFormData(prev => ({
                                ...prev,
                                assinantes: [...prev.assinantes, user.id]
                              }));
                            } else {
                              setFormData(prev => ({
                                ...prev,
                                assinantes: prev.assinantes.filter(id => id !== user.id)
                              }));
                            }
                          }}
                        />
                        <span style={{ fontSize: '14px' }}>
                          {user.nome} ({user.email})
                        </span>
                      </label>
                    ))
                  )}
                </div>
                {errors.assinantes && (
                  <span style={{ color: '#dc2626', fontSize: '12px' }}>
                    {errors.assinantes}
                  </span>
                )}
              </div>

              {/* Campo Prazo de Assinatura - só aparece quando há assinantes */}
              {formData.assinantes && formData.assinantes.length > 0 && (
                <div>
                  <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                    Prazo para Assinatura
                  </label>
                  <input
                    type="date"
                    value={formData.prazo_assinatura}
                    onChange={(e) => setFormData(prev => ({ ...prev, prazo_assinatura: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                  />
                  {errors.prazo_assinatura && (
                    <span style={{ color: '#dc2626', fontSize: '12px' }}>
                      {errors.prazo_assinatura[0]}
                    </span>
                  )}
                </div>
              )}
            </FormRow>
          )}

          {/* Campo de Status - apenas para Admin e Super Admin */}
          {(user?.tipo_usuario === 1 || user?.tipo_usuario === 2) && (
            <FormRow>
              <div>
                <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
                  Status
                </label>
                <select
                  value={formData.status}
                  onChange={(e) => setFormData(prev => ({ ...prev, status: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                >
                  <option value="rascunho">Rascunho</option>
                  <option value="enviado">Enviado</option>
                  <option value="assinado">Assinado</option>
                  <option value="cancelado">Cancelado</option>
                  <option value="arquivo">Arquivo</option>
                </select>
                {errors.status && (
                  <span style={{ color: '#dc2626', fontSize: '12px' }}>
                    {errors.status[0]}
                  </span>
                )}
              </div>
            </FormRow>
          )}

          <FormRow>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', fontWeight: '500' }}>
                Arquivo {!editingDocument && '*'}
              </label>
              <FileUploadArea
                className={`${dragOver ? 'dragover' : ''} ${errors.arquivo ? 'error' : ''}`}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => document.getElementById('file-input').click()}
                style={{
                  borderColor: errors.arquivo ? '#dc2626' : (formData.arquivo ? '#10b981' : '#d1d5db'),
                  backgroundColor: errors.arquivo ? '#fef2f2' : (formData.arquivo ? '#f0fdf4' : '#ffffff')
                }}
              >
                <input
                  id="file-input"
                  type="file"
                  accept={uploadConfig.allowed_file_types ? uploadConfig.allowed_file_types.split(',').map(type => `.${type}`).join(',') : '.pdf,.doc,.docx,.txt'}
                  onChange={(e) => handleFileSelect(e.target.files[0])}
                  style={{ display: 'none' }}
                />
                {formData.arquivo ? (
                  <FileInfo>
                    <span style={{ color: '#10b981', fontSize: '20px' }}>✅</span>
                    <span style={{ color: '#059669', fontWeight: '500' }}>{formData.arquivo.name}</span>
                    <span style={{ fontSize: '12px', color: '#6b7280' }}>({(formData.arquivo.size / (1024 * 1024)).toFixed(2)}MB)</span>
                    <Button
                      type="button"
                      size="sm"
                      $variant="outline"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleFileSelect(null); // Usar a função para limpar corretamente
                      }}
                    >
                      Remover
                    </Button>
                  </FileInfo>
                ) : errors.arquivo ? (
                  <div style={{ textAlign: 'center' }}>
                    <span style={{ color: '#dc2626', fontSize: '24px', display: 'block', marginBottom: '8px' }}>❌</span>
                    <p style={{ color: '#dc2626', fontWeight: '500', marginBottom: '4px' }}>Arquivo inválido</p>
                    <p style={{ fontSize: '12px', color: '#6b7280' }}>
                      Clique para selecionar um arquivo válido
                    </p>
                    <p style={{ fontSize: '11px', color: '#9ca3af', marginTop: '4px' }}>
                      Formatos aceitos: {uploadConfig.allowed_file_types ? uploadConfig.allowed_file_types.toUpperCase() : 'PDF, DOC, DOCX, TXT'}
                    </p>
                  </div>
                ) : (
                  <div style={{ textAlign: 'center' }}>
                    <span style={{ fontSize: '24px', display: 'block', marginBottom: '8px' }}>📁</span>
                    <p style={{ fontWeight: '500', marginBottom: '4px' }}>Clique aqui ou arraste um arquivo</p>
                    <p style={{ fontSize: '12px', color: '#6b7280' }}>
                      Formatos aceitos: {uploadConfig.allowed_file_types ? uploadConfig.allowed_file_types.toUpperCase() : 'PDF, DOC, DOCX, TXT'}
                    </p>
                    <p style={{ fontSize: '11px', color: '#9ca3af', marginTop: '4px' }}>
                      Tamanho máximo: {uploadConfig.max_file_size || 10}MB
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

      {/* Modal de Visualização */}
      {showViewModal && (
        <ViewModal onClick={handleCloseViewModal}>
          <ViewModalContent onClick={(e) => e.stopPropagation()}>
            <ViewModalHeader>
              <ViewModalTitle>
                {viewingDocument?.nome_arquivo || 'Visualizar Documento'}
              </ViewModalTitle>
              <ViewModalActions>
                <Button
                  size="sm"
                  $variant="outline"
                  onClick={handleDownloadDocument}
                >
                  <FaDownload style={{ marginRight: '8px' }} />
                  Download
                </Button>
                <Button
                  size="sm"
                  $variant="outline"
                  onClick={handleCloseViewModal}
                >
                  <FaTimes />
                </Button>
              </ViewModalActions>
            </ViewModalHeader>
            <ViewModalBody>
              {renderDocumentContent()}
            </ViewModalBody>
          </ViewModalContent>
        </ViewModal>
      )}
    </PageContainer>
  );
};

export default Documents;