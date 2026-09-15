export type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

export type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type ForgotPasswordForm = {
    email: string;
};

export type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};
