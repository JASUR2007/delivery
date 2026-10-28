import React from 'react';
import { useForm, Head } from '@inertiajs/react';
import { Building2, LogIn, Shield, UserCheck, Lock, Mail } from 'lucide-react';

export default function Login({ demo_users = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        email: 'ijrochi@davlat.uz',
        password: 'password',
        remember: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    const handleSelectDemoUser = (userEmail) => {
        setData({
            ...data,
            email: userEmail,
            password: 'password',
        });
    };

    const getRoleBadge = (role) => {
        switch (role) {
            case 'executor':
                return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'supervisor':
                return 'bg-amber-100 text-amber-700 border-amber-200';
            case 'accountant':
                return 'bg-emerald-100 text-emerald-700 border-emerald-200';
            default:
                return 'bg-slate-100 text-slate-700 border-slate-200';
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
            <Head title="Tizimga kirish" />

            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <div className="flex justify-center">
                    <div className="p-3 bg-indigo-600 rounded-xl text-white shadow-md">
                        <Building2 className="w-8 h-8" />
                    </div>
                </div>
                <h2 className="mt-4 text-center text-2xl font-bold tracking-tight text-slate-900">
                    Davlat Shartnomalari
                </h2>
                <p className="mt-1 text-center text-sm text-slate-500">
                    Jarima hisoblash va buxgalteriya moduli
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow-sm sm:rounded-xl sm:px-10 border border-slate-200 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Elektron pochta (Email)
                            </label>
                            <div className="relative rounded-lg">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Mail className="w-4 h-4" />
                                </div>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="ijrochi@davlat.uz"
                                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                            </div>
                            {errors.email && (
                                <p className="text-xs text-rose-600 mt-1">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Parol
                            </label>
                            <div className="relative rounded-lg">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Lock className="w-4 h-4" />
                                </div>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                            </div>
                            {errors.password && (
                                <p className="text-xs text-rose-600 mt-1">{errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-between text-xs">
                            <label className="flex items-center text-slate-600 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mr-2"
                                />
                                <span>Meni eslab qolish</span>
                            </label>
                            <span className="text-slate-400">Standart parol: password</span>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-xs text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition disabled:opacity-50"
                        >
                            <LogIn className="w-4 h-4 mr-2" />
                            <span>{processing ? 'Kirilmoqda...' : 'Tizimga kirish'}</span>
                        </button>
                    </form>

                    {/* Quick Demo Accounts Selection */}
                    {demo_users.length > 0 && (
                        <div className="pt-4 border-t border-slate-200">
                            <div className="flex items-center space-x-1.5 mb-2">
                                <Shield className="w-4 h-4 text-indigo-600" />
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Demo hisoblar:
                                </h3>
                            </div>
                            <p className="text-xs text-slate-500 mb-3">
                                Foydalanuvchini tanlang (email va parol avtomatik to‘ldiriladi):
                            </p>

                            <div className="space-y-2">
                                {demo_users.map((user) => {
                                    const isSelected = data.email === user.email;
                                    return (
                                        <button
                                            key={user.id}
                                            type="button"
                                            onClick={() => handleSelectDemoUser(user.email)}
                                            className={`w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition text-left cursor-pointer border ${
                                                isSelected
                                                    ? 'bg-indigo-50/80 border-indigo-400 ring-1 ring-indigo-400 text-indigo-900'
                                                    : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'
                                            }`}
                                        >
                                            <div className="flex items-center space-x-2">
                                                <UserCheck className={`w-3.5 h-3.5 ${isSelected ? 'text-indigo-600' : 'text-slate-400'}`} />
                                                <div>
                                                    <span className="font-semibold text-slate-900">{user.name}</span>
                                                    <span className="text-slate-500 ml-1.5 text-[11px]">({user.email})</span>
                                                </div>
                                            </div>
                                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold border ${getRoleBadge(user.role)}`}>
                                                {user.role_label}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
