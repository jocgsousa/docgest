import React from 'react';
import styled from 'styled-components';

const InputContainer = styled.div`
  display: flex;
  flex-direction: column;
  margin-bottom: 16px;
`;

const Label = styled.label`
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 4px;

  .required {
    color: #ef4444;
  }
`;

const StyledInput = styled.input`
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: white;

  &:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  &:disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
  }

  &.error {
    border-color: #ef4444;
    
    &:focus {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
  }

  ${props => props.size === 'small' && `
    padding: 6px 8px;
    font-size: 13px;
  `}

  ${props => props.size === 'large' && `
    padding: 12px 16px;
    font-size: 16px;
  `}
`;

const StyledTextarea = styled.textarea`
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: white;
  resize: vertical;
  min-height: 80px;
  font-family: inherit;

  &:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  &:disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
  }

  &.error {
    border-color: #ef4444;
    
    &:focus {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
  }
`;

const StyledSelect = styled.select`
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: white;
  cursor: pointer;

  &:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  &:disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
  }

  &.error {
    border-color: #ef4444;
    
    &:focus {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
  }
`;

const ErrorMessage = styled.span`
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;

  i {
    font-size: 10px;
  }
`;

const HelpText = styled.span`
  color: #6b7280;
  font-size: 12px;
  margin-top: 4px;
`;

const Input = ({
  label,
  type = 'text',
  placeholder,
  value,
  onChange,
  onBlur,
  disabled = false,
  required = false,
  error,
  helpText,
  size = 'medium',
  options = [],
  rows = 4,
  className,
  ...props
}) => {
  const inputProps = {
    type,
    placeholder,
    value,
    onChange,
    onBlur,
    disabled,
    required,
    className: `${error ? 'error' : ''} ${className || ''}`.trim(),
    size,
    ...props
  };

  const renderInput = () => {
    if (type === 'textarea') {
      return <StyledTextarea rows={rows} {...inputProps} />;
    }
    
    if (type === 'select') {
      return (
        <StyledSelect {...inputProps}>
          <option value="">Selecione...</option>
          {options.map((option, index) => (
            <option key={index} value={option.value}>
              {option.label}
            </option>
          ))}
        </StyledSelect>
      );
    }

    return <StyledInput {...inputProps} />;
  };

  return (
    <InputContainer>
      {label && (
        <Label>
          {label}
          {required && <span className="required">*</span>}
        </Label>
      )}
      {renderInput()}
      {error && (
        <ErrorMessage>
          <i className="fas fa-exclamation-circle" />
          {error}
        </ErrorMessage>
      )}
      {!error && helpText && <HelpText>{helpText}</HelpText>}
    </InputContainer>
  );
};

export default Input;