import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from './Button';
import Card from './Card';
import Input from './Input';
import Toast from './Toast';
import useToast from '../hooks/useToast';
import { Send, Users, User, Building2, AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';

const ManagerContainer = styled.div`
  display: grid;
  gap: 24px;
`;

const NotificationForm = styled(Card)`
  padding: 24px;
`;

const FormTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
`;

const FormGrid = styled.div`
  display: grid;
  gap: 16px;
  margin-bottom: 20px;
`;

const FormRow = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
`;

const TextArea = styled.textarea`
  width: 100%;
  min-height: 100px;
  padding: 12px;
  border: 1px solid ${props => props.theme.colors.border};
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  
  &:focus {
    outline: none;
    border-color: ${props => props.theme.colors.primary};
    box-shadow: 0 0 0 3px ${props => props.theme.colors.primary}20;
  }
  
  &:disabled {
    background-color: ${props => props.theme.colors.gray[100]};
    cursor: not-allowed;
  }
`;

const Select = styled.select`
  width: 100%;
  padding: 12px;
  border: 1px solid ${props => props.theme.colors.border};
  border-radius: 8px;
  font-size: 14px;
  background-color: white;
  
  &:focus {
    outline: none;
    border-color: ${props => props.theme.colors.primary};
    box-shadow: 0 0 0 3px ${props => props.theme.colors.primary}20;
  }
  
  &:disabled {
    background-color: ${props => props.theme.colors.gray[100]};
    cursor: not-allowed;
  }
`;

const Label = styled.label`
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: ${props => props.theme.colors.text};
  margin-bottom: 6px;
`;

const RadioGroup = styled.div`
  display: flex;
  gap: 16px;
  margin-top: 8px;
`;

const RadioOption = styled.label`
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: ${props => props.theme.colors.text};
  
  input[type="radio"] {
    margin: 0;
  }
`;

const TypeIcon = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  
  &.info {
    background-color: ${props => props.theme.colors.blue}20;
    color: ${props => props.theme.colors.blue};
  }
  
  &.success {
    background-color: ${props => props.theme.colors.green}20;
    color: ${props => props.theme.colors.green};
  }
  
  &.warning {
    background-color: ${props => props.theme.colors.yellow}20;
    color: ${props => props.theme.colors.yellow};
  }
  
  &.error {
    background-color: ${props => props.theme.colors.red}20;
    color: ${props => props.theme.colors.red};
  }
`;

const ButtonGroup = styled.div`
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  
  @media (max-width: 768px) {
    flex-direction: column;
  }
`;

const ErrorMessage = styled.div`
  color: ${props => props.theme.colors.red};
  font-size: 14px;
  margin-top: 4px;
`;

const AlertMessage = styled.div`
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  margin: 16px 0;
  border: 1px solid;
  
  &.success {
    background-color: ${props => props.theme.colors.green}10;
    border-color: ${props => props.theme.colors.green}30;
    color: ${props => props.theme.colors.green};
  }
  
  &.error {
    background-color: ${props => props.theme.colors.red}10;
    border-color: ${props => props.theme.colors.red}30;
    color: ${props => props.theme.colors.red};
  }
  
  &.info {
    background-color: ${props => props.theme.colors.blue}10;
    border-color: ${props => props.theme.colors.blue}30;
    color: ${props => props.theme.colors.blue};
  }
`;

const AlertIcon = styled.div`
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
`;

const AlertContent = styled.div`
  flex: 1;
`;

const AlertTitle = styled.div`
  font-weight: 600;
  margin-bottom: 4px;
`;

const AlertDescription = styled.div`
  font-weight: 400;
  opacity: 0.9;
`;

function NotificationManager() {
  const { user } = useAuth();
  const { toasts, removeToast, showSuccess, showError, showInfo } = useToast();
  const [loading, setLoading] = useState(false);
  const [users, setUsers] = useState([]);
  const [formData, setFormData] = useState({
    titulo: '',
    mensagem: '',
    tipo: 'info',
    recipient_type: 'all', // all, specific, company
    usuario_destinatario_id: ''
  });
  const [errors, setErrors] = useState({});
  const [feedback, setFeedback] = useState(null); // { type: 'success'|'error'|'info', title: '', message: '', details: '' }

  const isSuperAdmin = user?.tipo_usuario === 1;
  const isCompanyAdmin = user?.tipo_usuario === 2;

  useEffect(() => {
    if (isSuperAdmin || isCompanyAdmin) {
      fetchUsers();
    }
  }, [isSuperAdmin, isCompanyAdmin]);

  const fetchUsers = async () => {
    try {
      const response = await api.get('/users');
      // A API retorna uma estrutura paginada: response.data.data.items
      setUsers(response.data.data?.items || []);
    } catch (error) {
      console.error('Erro ao carregar usuários:', error);
      setUsers([]);
    }
  };

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
    setFeedback(null);
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.titulo.trim()) {
      newErrors.titulo = 'Título é obrigatório';
    }

    if (!formData.mensagem.trim()) {
      newErrors.mensagem = 'Mensagem é obrigatória';
    }

    if (formData.recipient_type === 'specific' && !formData.usuario_destinatario_id) {
      newErrors.usuario_destinatario_id = 'Selecione um usuário';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setFeedback(null);

    try {
      const payload = {
        titulo: formData.titulo,
        mensagem: formData.mensagem,
        tipo: formData.tipo
      };

      // Configurar destinatários baseado no tipo selecionado
      if (formData.recipient_type === 'all') {
        if (isSuperAdmin) {
          payload.send_to_all = true;
        }
        // Para admin da empresa, não precisa especificar nada (enviará para todos da empresa)
      } else if (formData.recipient_type === 'specific') {
        payload.usuario_destinatario_id = formData.usuario_destinatario_id;
      }

      const response = await api.post('/notifications', payload);
      
      // Preparar mensagem de sucesso detalhada
      let successMessage = 'Notificação enviada com sucesso!';
      let successDetails = '';
      
      if (response.data?.notifications_created) {
        const count = response.data.notifications_created;
        successDetails = `${count} notificação${count > 1 ? 'ões' : ''} ${count > 1 ? 'foram criadas' : 'foi criada'}.`;
      }
      
      if (formData.recipient_type === 'all') {
        if (isSuperAdmin) {
          successDetails += ' Enviada para todos os usuários do sistema.';
        } else {
          successDetails += ' Enviada para todos os usuários da sua empresa.';
        }
      } else if (formData.recipient_type === 'specific') {
        const selectedUser = getFilteredUsers().find(u => u.id == formData.usuario_destinatario_id);
        if (selectedUser) {
          successDetails += ` Enviada para ${selectedUser.nome} (${selectedUser.email}).`;
        }
      }
      
      setFeedback({
        type: 'success',
        title: 'Notificação Enviada!',
        message: successMessage,
        details: successDetails
      });
      
      // Mostrar toast de sucesso
      showSuccess(
        'Notificação Enviada!',
        successDetails || successMessage
      );
      
      // Limpar formulário
      setFormData({
        titulo: '',
        mensagem: '',
        tipo: 'info',
        recipient_type: 'all',
        usuario_destinatario_id: ''
      });
      
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Erro ao enviar notificação';
      const errorDetails = error.response?.data?.details || 'Verifique os dados e tente novamente.';
      
      setFeedback({
        type: 'error',
        title: 'Erro no Envio',
        message: errorMessage,
        details: errorDetails
      });
      
      // Mostrar toast de erro
      showError(
        'Erro no Envio',
        errorMessage
      );
    } finally {
      setLoading(false);
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'success':
        return <CheckCircle size={16} />;
      case 'warning':
        return <AlertTriangle size={16} />;
      case 'error':
        return <AlertCircle size={16} />;
      default:
        return <Info size={16} />;
    }
  };

  const getFeedbackIcon = (type) => {
    switch (type) {
      case 'success':
        return <CheckCircle size={20} />;
      case 'error':
        return <AlertCircle size={20} />;
      case 'info':
        return <Info size={20} />;
      default:
        return <Info size={20} />;
    }
  };

  const renderFeedback = () => {
    if (!feedback) return null;

    return (
      <AlertMessage className={feedback.type}>
        <AlertIcon>
          {getFeedbackIcon(feedback.type)}
        </AlertIcon>
        <AlertContent>
          <AlertTitle>{feedback.title}</AlertTitle>
          <AlertDescription>
            {feedback.message}
            {feedback.details && (
              <div style={{ marginTop: '4px' }}>{feedback.details}</div>
            )}
          </AlertDescription>
        </AlertContent>
      </AlertMessage>
    );
  };

  const getRecipientOptions = () => {
    if (isSuperAdmin) {
      return [
        { value: 'all', label: 'Todos os usuários do sistema', icon: <Users size={16} /> },
        { value: 'specific', label: 'Usuário específico', icon: <User size={16} /> }
      ];
    } else if (isCompanyAdmin) {
      return [
        { value: 'all', label: 'Todos os usuários da empresa', icon: <Building2 size={16} /> },
        { value: 'specific', label: 'Usuário específico', icon: <User size={16} /> }
      ];
    }
    return [];
  };

  // Filtrar usuários baseado no tipo de admin
  const getFilteredUsers = () => {
    if (!Array.isArray(users)) {
      return [];
    }
    
    let filteredUsers = [];
    
    if (isSuperAdmin) {
      filteredUsers = users;
    } else if (isCompanyAdmin) {
      filteredUsers = users.filter(u => u.empresa_id === user.empresa_id);
    }
    
    // Excluir o próprio usuário da lista
    return filteredUsers.filter(u => u.id !== user.id);
  };

  if (!isSuperAdmin && !isCompanyAdmin) {
    return (
      <Card>
        <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
          Você não tem permissão para gerenciar notificações.
        </div>
      </Card>
    );
  }

  return (
    <>
      <ManagerContainer>
        <NotificationForm>
          <FormTitle>
            <Send size={20} />
            Enviar Notificação
          </FormTitle>
        
        <form onSubmit={handleSubmit}>
          <FormGrid>
            <div>
              <Label>Título *</Label>
              <Input
                value={formData.titulo}
                onChange={(e) => handleInputChange('titulo', e.target.value)}
                placeholder="Digite o título da notificação"
                disabled={loading}
              />
              {errors.titulo && <ErrorMessage>{errors.titulo}</ErrorMessage>}
            </div>

            <div>
              <Label>Tipo de Notificação</Label>
              <Select
                value={formData.tipo}
                onChange={(e) => handleInputChange('tipo', e.target.value)}
                disabled={loading}
              >
                <option value="info">Informação</option>
                <option value="success">Sucesso</option>
                <option value="warning">Aviso</option>
                <option value="error">Erro</option>
              </Select>
              <TypeIcon className={formData.tipo}>
                {getTypeIcon(formData.tipo)}
                {formData.tipo === 'info' && 'Informação'}
                {formData.tipo === 'success' && 'Sucesso'}
                {formData.tipo === 'warning' && 'Aviso'}
                {formData.tipo === 'error' && 'Erro'}
              </TypeIcon>
            </div>

            <div style={{ gridColumn: '1 / -1' }}>
              <Label>Mensagem *</Label>
              <TextArea
                value={formData.mensagem}
                onChange={(e) => handleInputChange('mensagem', e.target.value)}
                placeholder="Digite a mensagem da notificação"
                disabled={loading}
              />
              {errors.mensagem && <ErrorMessage>{errors.mensagem}</ErrorMessage>}
            </div>

            <div style={{ gridColumn: '1 / -1' }}>
              <Label>Destinatários</Label>
              <RadioGroup>
                {getRecipientOptions().map(option => (
                  <RadioOption key={option.value}>
                    <input
                      type="radio"
                      name="recipient_type"
                      value={option.value}
                      checked={formData.recipient_type === option.value}
                      onChange={(e) => handleInputChange('recipient_type', e.target.value)}
                      disabled={loading}
                    />
                    {option.icon}
                    {option.label}
                  </RadioOption>
                ))}
              </RadioGroup>
            </div>

            {formData.recipient_type === 'specific' && (
              <div style={{ gridColumn: '1 / -1' }}>
                <Label>Selecionar Usuário *</Label>
                <Select
                  value={formData.usuario_destinatario_id}
                  onChange={(e) => handleInputChange('usuario_destinatario_id', e.target.value)}
                  disabled={loading}
                >
                  <option value="">Selecione um usuário</option>
                  {getFilteredUsers().map(user => (
                    <option key={user.id} value={user.id}>
                      {user.nome} ({user.email})
                    </option>
                  ))}
                </Select>
                {errors.usuario_destinatario_id && (
                  <ErrorMessage>{errors.usuario_destinatario_id}</ErrorMessage>
                )}
              </div>
            )}
          </FormGrid>

          {renderFeedback()}

          <ButtonGroup>
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setFormData({
                  titulo: '',
                  mensagem: '',
                  tipo: 'info',
                  recipient_type: 'all',
                  usuario_destinatario_id: ''
                });
                setErrors({});
                setFeedback(null);
              }}
              disabled={loading}
            >
              Limpar
            </Button>
            <Button
              type="submit"
              variant="primary"
              loading={loading}
              disabled={loading}
            >
              <Send size={16} />
              Enviar Notificação
            </Button>
          </ButtonGroup>
        </form>
      </NotificationForm>
    </ManagerContainer>
    
    <Toast toasts={toasts} removeToast={removeToast} />
  </>
  );
}

export default NotificationManager;