import React from 'react';
import { cn } from '../../lib/utils';

export function Button({
  children,
  variant = 'default',
  size = 'default',
  className,
  disabled,
  ...props
}) {
  const baseClasses = 'acervox-btn';
  const variantClasses = {
    default: 'acervox-btn-primary',
    secondary: 'acervox-btn-secondary',
    outline: 'acervox-btn-outline',
    ghost: 'acervox-btn-ghost',
    destructive: 'acervox-btn-destructive',
  };

  const sizeClasses = {
    default: '',
    sm: 'acervox-btn-sm',
    lg: 'acervox-btn-lg',
    icon: 'acervox-btn-icon',
  };

  return (
    <button
      className={cn(
        baseClasses,
        variantClasses[variant],
        sizeClasses[size],
        className
      )}
      disabled={disabled}
      {...props}
    >
      {children}
    </button>
  );
}
