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

const ButtonGroup = styled.div`
  display: flex;
  gap: 12px;
`;

const ImportSection = styled.div`
  display: flex;
  flex-direction: column;
  gap: 16px;
`;

const FileInput = styled.input`
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
`;

const ImportResult = styled.div`
  padding: 16px;
  border-radius: 8px;
  margin-top: 16px;
  
  &.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
  }
  
  &.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
  }
`;

const ImportStats = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 12px;
  margin-top: 12px;
  padding: 12px;
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 6px;
`;

const StatItem = styled.div`
  text-align: center;
  padding: 8px;
  border-radius: 4px;
  background-color: rgba(255, 255, 255, 0.5);
  
  .stat-number {
    font-size: 18px;
    font-weight: bold;
    display: block;
    margin-bottom: 4px;
  }
  
  .stat-label {
    font-size: 12px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
`;

const ErrorList = styled.ul`
  margin: 8px 0 0 0;
  padding-left: 20px;
`;

const PageTitle = styled.h1`
  font-size: 24px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
`;

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 16px;
`;

const StatusBadge = styled.span`
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  
  ${props => {
    if (props.status === 1) {
      return `
        background-color: #dcfce7;
        color: #166534;
      `;
    } else {
      return `
        background-color: #fef2f2;
        color: #991b1b;
      `;
    }
  }}
`;

const Professions = () => {
  const { user } = useAuth();
  const [professions, setProfessions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingProfession, setEditingProfession] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    descricao: ''
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  
  // Estados para importação de CSV
  const [showImportModal, setShowImportModal] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [importResult, setImportResult] = useState(null);
  
  // Estados para paginação
  const [pagination, setPagination] = useState({
    currentPage: 1,
    pageSize: 10,
    totalItems: 0,
    totalPages: 0
  });
  const [filters, setFilters] = useState({
    search: '',
    ativo: ''
  });

  const columns = [
    {
      key: 'nome',
      title: 'Nome',
      sortable: true
    },
    {
      key: 'descricao',
      title: 'Descrição'
    },
    {
      key: 'ativo',
      title: 'Status',
      render: (value) => (
        <StatusBadge status={value}>
          {value === 1 ? 'Ativo' : 'Inativo'}
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
      render: (_, profession) => (
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button
            variant="outline"
            size="small"
            onClick={() => handleEdit(profession)}
          >
            Editar
          </Button>
          {profession.ativo === 1 ? (
            <Button
              variant="danger"
              size="small"
              onClick={() => handleDeactivate(profession.id)}
            >
              Desativar
            </Button>
          ) : (
            <Button
              variant="success"
              size="small"
              onClick={() => handleActivate(profession.id)}
            >
              Ativar
            </Button>
          )}
        </div>
      )
    }
  ];

  const loadProfessions = async () => {
    try {
      setLoading(true);
      const params = {
        page: pagination.currentPage,
        pageSize: pagination.pageSize,
        search: filters.search,
        ativo: filters.ativo
      };
      
      const response = await api.get('/professions', { params });
      const data = response.data.data;
      
      // A API retorna dados paginados com estrutura padrão
      if (data?.items) {
        // Se data tem estrutura paginada com items
        setProfessions(data.items || []);
        setPagination(prev => ({
          ...prev,
          totalItems: data.pagination?.total || 0,
          totalPages: data.pagination?.total_pages || 0,
          currentPage: data.pagination?.current_page || 1
        }));
      } else if (Array.isArray(data)) {
        // Se data é um array, usar diretamente (fallback)
        setProfessions(data);
        setPagination(prev => ({
          ...prev,
          totalItems: data.length,
          totalPages: Math.ceil(data.length / pagination.pageSize),
          currentPage: 1
        }));
      } else {
        // Fallback para array vazio
        setProfessions([]);
        setPagination(prev => ({
          ...prev,
          totalItems: 0,
          totalPages: 0,
          currentPage: 1
        }));
      }
    } catch (error) {
      console.error('Erro ao carregar profissões:', error);
      setProfessions([]);
      setPagination(prev => ({ ...prev, totalItems: 0, totalPages: 0 }));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Carregar profissões para todos os usuários para testar paginação
    if (user) {
      loadProfessions();
    }
  }, [user, pagination.currentPage, pagination.pageSize, filters.search, filters.ativo]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      if (editingProfession) {
        await api.put(`/professions/${editingProfession.id}`, formData);
      } else {
        await api.post('/professions', formData);
      }

      setShowModal(false);
      setEditingProfession(null);
      setFormData({
        nome: '',
        descricao: ''
      });
      loadProfessions();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(formatErrors(error.response.data.errors));
      } else {
        console.error('Erro ao salvar profissão:', error);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleEdit = (profession) => {
    setEditingProfession(profession);
    setFormData({
      nome: profession.nome || '',
      descricao: profession.descricao || ''
    });
    setShowModal(true);
  };

  const handleCreate = () => {
    setEditingProfession(null);
    setFormData({
      nome: '',
      descricao: ''
    });
    setErrors({});
    setShowModal(true);
  };

  const handleDeactivate = async (id) => {
    if (window.confirm('Tem certeza que deseja desativar esta profissão?')) {
      try {
        await api.delete(`/professions/${id}`);
        loadProfessions();
      } catch (error) {
        console.error('Erro ao desativar profissão:', error);
        alert('Erro ao desativar profissão. Verifique se não há usuários associados.');
      }
    }
  };

  const handleActivate = async (id) => {
    try {
      await api.post(`/professions/${id}/activate`);
      loadProfessions();
    } catch (error) {
      console.error('Erro ao ativar profissão:', error);
    }
  };

  const handleImport = () => {
    setShowImportModal(true);
    setSelectedFile(null);
    setImportResult(null);
  };

  const handleFileSelect = (event) => {
    const file = event.target.files[0];
    setSelectedFile(file);
    setImportResult(null);
  };

  const handleImportSubmit = async () => {
    if (!selectedFile) {
      alert('Por favor, selecione um arquivo CSV.');
      return;
    }

    setImporting(true);
    setImportResult(null);

    try {
      const formData = new FormData();
      formData.append('csv_file', selectedFile);

      const response = await api.post('/professions/import', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      console.log('Resposta completa da API:', response);
      console.log('Dados da resposta:', response.data);
      console.log('Data dentro da resposta:', response.data.data);

      setImportResult({
        success: true,
        message: response.data.message,
        data: response.data.data
      });

      // Recarrega a lista de profissões
      loadProfessions();

    } catch (error) {
      console.error('Erro ao importar CSV:', error);
      setImportResult({
        success: false,
        message: error.response?.data?.message || 'Erro ao importar arquivo CSV',
        errors: error.response?.data?.data?.errors || []
      });
    } finally {
      setImporting(false);
    }
  };

  const closeImportModal = () => {
    setShowImportModal(false);
    setSelectedFile(null);
    setImportResult(null);
  };
  
  const handlePageChange = (page) => {
    setPagination(prev => ({ ...prev, currentPage: page }));
  };
  
  const handlePageSizeChange = (pageSize) => {
    setPagination(prev => ({ ...prev, pageSize, currentPage: 1 }));
  };
  
  const handleSearchChange = (search) => {
    setFilters(prev => ({ ...prev, search }));
    setPagination(prev => ({ ...prev, currentPage: 1 }));
  };
  
  const handleStatusChange = (ativo) => {
    setFilters(prev => ({ ...prev, ativo }));
    setPagination(prev => ({ ...prev, currentPage: 1 }));
  };

  // Permitir acesso para todos os usuários para testar paginação
  // if (user?.tipo_usuario !== 1) {
  //   return (
  //     <PageContainer>
  //       <Card>
  //         <p>Acesso negado. Apenas super administradores podem gerenciar profissões.</p>
  //       </Card>
  //     </PageContainer>
  //   );
  // }

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Gerenciar Profissões</PageTitle>
        <ButtonGroup>
          <Button variant="outline" onClick={handleImport}>
            Importar CSV
          </Button>
          <Button onClick={handleCreate}>
            Nova Profissão
          </Button>
        </ButtonGroup>
      </PageHeader>

      <Card>
        <div style={{ marginBottom: '16px', display: 'flex', gap: '12px', alignItems: 'center' }}>
          <Input
            placeholder="Buscar por nome..."
            value={filters.search}
            onChange={(e) => handleSearchChange(e.target.value)}
            style={{ maxWidth: '300px' }}
          />
          <select
            value={filters.ativo}
            onChange={(e) => handleStatusChange(e.target.value)}
            style={{
              padding: '8px 12px',
              border: '1px solid #d1d5db',
              borderRadius: '6px',
              fontSize: '14px',
              background: 'white'
            }}
          >
            <option value="">Todos os status</option>
            <option value="1">Ativo</option>
            <option value="0">Inativo</option>
          </select>
        </div>
        
        <Table
          columns={columns}
          data={professions}
          loading={loading}
          emptyMessage="Nenhuma profissão encontrada"
        />
        
        <Pagination
          currentPage={pagination.currentPage}
          totalPages={Math.ceil(pagination.totalItems / pagination.pageSize)}
          totalItems={pagination.totalItems}
          pageSize={pagination.pageSize}
          onPageChange={handlePageChange}
          onPageSizeChange={handlePageSizeChange}
          pageSizeOptions={[10, 25, 50, 100]}
        />
      </Card>

      <Modal
        isOpen={showModal}
        onClose={() => {
          setShowModal(false);
          setEditingProfession(null);
          setErrors({});
        }}
        title={editingProfession ? 'Editar Profissão' : 'Nova Profissão'}
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
              label="Descrição"
              value={formData.descricao}
              onChange={(e) => setFormData(prev => ({ ...prev, descricao: e.target.value }))}
              error={errors.descricao?.[0]}
              multiline
              rows={3}
            />
          </FormGrid>

          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setShowModal(false);
                setEditingProfession(null);
                setErrors({});
              }}
            >
              Cancelar
            </Button>
            <Button type="submit" loading={submitting}>
              {editingProfession ? 'Atualizar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={showImportModal}
        onClose={closeImportModal}
        title="Importar Profissões do CSV"
      >
        <ImportSection>
          <div>
            <p style={{ marginBottom: '12px', fontSize: '14px', color: '#666' }}>
              Selecione um arquivo CSV com as colunas: <strong>CODIGO</strong> e <strong>TITULO</strong>
            </p>
            <FileInput
              type="file"
              accept=".csv"
              onChange={handleFileSelect}
            />
          </div>

          {selectedFile && (
            <div style={{ fontSize: '14px', color: '#666' }}>
              Arquivo selecionado: <strong>{selectedFile.name}</strong>
            </div>
          )}

          {importResult && (
            <ImportResult className={importResult.success ? 'success' : 'error'}>
              <div style={{ fontWeight: '500', marginBottom: '8px' }}>
                {importResult.message}
              </div>
              
              {importResult.success && importResult.data && (
                <ImportStats>
                  <StatItem>
                    <span className="stat-number" style={{ color: '#28a745' }}>
                      {importResult.data.imported}
                    </span>
                    <span className="stat-label">Criadas</span>
                  </StatItem>
                  
                  <StatItem>
                    <span className="stat-number" style={{ color: '#17a2b8' }}>
                      {importResult.data.updated}
                    </span>
                    <span className="stat-label">Atualizadas</span>
                  </StatItem>
                  
                  <StatItem>
                    <span className="stat-number" style={{ color: '#6c757d' }}>
                      {importResult.data.total_processed}
                    </span>
                    <span className="stat-label">Total</span>
                  </StatItem>
                  
                  {importResult.data.errors_count > 0 && (
                    <StatItem>
                      <span className="stat-number" style={{ color: '#dc3545' }}>
                        {importResult.data.errors_count}
                      </span>
                      <span className="stat-label">Erros</span>
                    </StatItem>
                  )}
                </ImportStats>
              )}
              
              {!importResult.success && importResult.errors && importResult.errors.length > 0 && (
                <div style={{ marginTop: '12px' }}>
                  <div style={{ fontWeight: '500', marginBottom: '8px', fontSize: '14px' }}>
                    Detalhes dos erros:
                  </div>
                  <ErrorList>
                    {importResult.errors.map((error, index) => (
                      <li key={index} style={{ marginBottom: '4px' }}>{error}</li>
                    ))}
                  </ErrorList>
                </div>
              )}
            </ImportResult>
          )}

          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              variant="outline"
              onClick={closeImportModal}
            >
              Cancelar
            </Button>
            <Button
              type="button"
              onClick={handleImportSubmit}
              loading={importing}
              disabled={!selectedFile}
            >
              Importar
            </Button>
          </div>
        </ImportSection>
      </Modal>
    </PageContainer>
  );
};

export default Professions;