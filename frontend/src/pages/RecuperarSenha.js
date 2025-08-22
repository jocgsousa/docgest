import React, { useState } from 'react';
import styled from 'styled-components';
import { Link, useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import { ArrowLeft, Mail, CheckCircle } from 'lucide-react';
import api from '../services/api';

const Container = styled.div`
  min-height: 100vh;
  background: linear-gradient(135deg, ${props => props.theme.colors.gray[50]} 0%, ${props => props.theme.colors.white} 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
`;

const RecoveryCard = styled(Card)`
  width: 100%;
  max-width: 400px;
  padding: 2rem;
`;

const Header = styled.div`
  text-align: center;
  margin-bottom: 2rem;
`;

const BackButton = styled(Link)`
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: ${props => props.theme.colors.gray[600]};
  text-decoration: none;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  
  &:hover {
    color: ${props => props.theme.colors.gray[900]};
  }
`;

const IconContainer = styled.div`
  width: 4rem;
  height: 4rem;
  background-color: ${props => props.theme.colors.primary + '20'};
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: ${props => props.theme.colors.primary};
`;

const Title = styled.h1`
  font-size: 1.875rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.5rem;
`;

const Subtitle = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  font-size: 1rem;
  line-height: 1.5;
`;

const Form = styled.form`
  display: flex;
  flex-direction: column;
  gap: 1rem;
`;

const ErrorMessage = styled.div`
  background-color: ${props => props.theme.colors.error + '15'};
  color: ${props => props.theme.colors.error};
  border: 2px solid ${props => props.theme.colors.error};
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px ${props => props.theme.colors.error + '25'};
  animation: slideIn 0.3s ease-out;
  position: relative;
  
  &::before {
    content: '⚠️';
    font-size: 1.2rem;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;

const SuccessMessage = styled.div`
  background-color: ${props => props.theme.colors.success + '15'};
  color: ${props => props.theme.colors.success};
  border: 2px solid ${props => props.theme.colors.success};
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px ${props => props.theme.colors.success + '25'};
  animation: slideIn 0.3s ease-out;
  position: relative;
  text-align: center;
  
  &::before {
    content: '✅';
    font-size: 1.2rem;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;

const SuccessIcon = styled.div`
  width: 4rem;
  height: 4rem;
  background-color: ${props => props.theme.colors.success + '20'};
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: ${props => props.theme.colors.success};
`;

const LoginLink = styled.div`
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  
  p {
    color: ${props => props.theme.colors.gray[600]};
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }
  
  a {
    color: ${props => props.theme.colors.primary};
    text-decoration: none;
    font-weight: 500;
    
    &:hover {
      text-decoration: underline;
    }
  }
`;

const Instructions = styled.div`
  background-color: ${props => props.theme.colors.gray[50]};
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  
  h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: ${props => props.theme.colors.gray[900]};
    margin-bottom: 0.5rem;
  }
  
  ul {
    font-size: 0.875rem;
    color: ${props => props.theme.colors.gray[600]};
    margin: 0;
    padding-left: 1.25rem;
    
    li {
      margin-bottom: 0.25rem;
    }
  }
`;

function RecuperarSenha() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleChange = (e) => {
    setEmail(e.target.value);
    setError('');
  };

  const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!email) {
      setError('Por favor, insira seu e-mail.');
      return;
    }

    if (!validateEmail(email)) {
      setError('Por favor, insira um e-mail válido.');
      return;
    }

    setLoading(true);

    try {
      await api.post('/auth/forgot-password', { email });
      setSuccess(true);
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Erro ao enviar e-mail de recuperação. Tente novamente.'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleBackToLogin = () => {
    navigate('/login');
  };

  if (success) {
    return (
      <Container>
        <RecoveryCard>
          <Header>
            <SuccessIcon>
              <CheckCircle size={24} />
            </SuccessIcon>
            <Title>E-mail Enviado!</Title>
            <Subtitle>
              Enviamos um link de recuperação para <strong>{email}</strong>. 
              Verifique sua caixa de entrada e siga as instruções para redefinir sua senha.
            </Subtitle>
          </Header>

          <Instructions>
            <h4>Próximos passos:</h4>
            <ul>
              <li>Verifique sua caixa de entrada</li>
              <li>Procure também na pasta de spam/lixo eletrônico</li>
              <li>Clique no link recebido para redefinir sua senha</li>
              <li>O link expira em 1 hora por segurança</li>
            </ul>
          </Instructions>

          <Button 
            variant="primary" 
            size="lg" 
            $fullWidth
            onClick={handleBackToLogin}
          >
            Voltar ao Login
          </Button>

          <LoginLink>
            <p>Não recebeu o e-mail?</p>
            <a href="#" onClick={(e) => { e.preventDefault(); setSuccess(false); setEmail(''); }}>
              Tentar novamente
            </a>
          </LoginLink>
        </RecoveryCard>
      </Container>
    );
  }

  return (
    <Container>
      <RecoveryCard>
        <BackButton to="/login">
          <ArrowLeft size={16} />
          Voltar ao login
        </BackButton>
        
        <Header>
          <IconContainer>
            <Mail size={24} />
          </IconContainer>
          <Title>Recuperar Senha</Title>
          <Subtitle>
            Digite seu e-mail e enviaremos um link para redefinir sua senha.
          </Subtitle>
        </Header>

        {error && <ErrorMessage>{error}</ErrorMessage>}

        <Form onSubmit={handleSubmit}>
          <Input
            label="E-mail"
            name="email"
            type="email"
            value={email}
            onChange={handleChange}
            placeholder="seu@email.com"
            required
          />

          <Button 
            type="submit" 
            variant="primary" 
            size="lg" 
            $fullWidth
            disabled={loading}
          >
            {loading ? 'Enviando...' : 'Enviar Link de Recuperação'}
          </Button>
        </Form>

        <LoginLink>
          <p>Lembrou da senha?</p>
          <Link to="/login">Fazer login</Link>
        </LoginLink>
      </RecoveryCard>
    </Container>
  );
}

export default RecuperarSenha;