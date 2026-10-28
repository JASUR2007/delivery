import React, { useState } from 'react';
import { usePage, router, Link } from '@inertiajs/react';
import { 
    FileText, 
    Wallet, 
    UserCheck, 
    AlertCircle, 
    CheckCircle2, 
    ShieldAlert,
    Building2,
    LogOut
} from 'lucide-react';

export default function AppLayout({ children, title }) {
    const { auth, flash = {} } = usePage().props;
    const currentUser = auth?.user;

    const getRoleColor = (role) => {
        switch (role) {
            case 'executor':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'supervisor':
                return 'bg-amber-100 text-amber-800 border-amber-200';
            case 'accountant':
                return 'bg-emerald-100 text-emerald-800 border-emerald-200';
            default:
                return 'bg-slate-100 text-slate-800 border-slate-200';
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
            {/* Top Navigation */}
            <header className="bg-white border-b border-slate-200 sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16 items-center">
                        <div className="flex items-center space-x-8">
                            <Link href="/" className="flex items-center space-x-3 text-slate-900 group">
                                <div className="p-2 bg-indigo-600 rounded-lg text-white group-hover:bg-indigo-700 transition">
                                    <Building2 className="w-5 h-5" />
                                </div>
                                <div>
                                    <h1 className="font-bold text-base tracking-tight leading-none text-slate-900">
                                        Davlat Shartnomalari
                                    </h1>
                                    <p className="text-xs text-slate-500 mt-0.5">Jarima va Buxgalteriya tizimi</p>
                                </div>
                            </Link>

                            <nav className="hidden md:flex space-x-1">
                                <Link
                                    href="/"
                                    className={`px-3 py-2 rounded-md text-sm font-medium transition ${
                                        window.location.pathname === '/' || window.location.pathname.startsWith('/contracts')
                                            ? 'bg-slate-100 text-slate-900'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    <div className="flex items-center space-x-1.5">
                                        <FileText className="w-4 h-4 text-slate-500" />
                                        <span>Shartnomalar</span>
                                    </div>
                                </Link>

                                <Link
                                    href="/budget"
                                    className={`px-3 py-2 rounded-md text-sm font-medium transition ${
                                        window.location.pathname.startsWith('/budget')
                                            ? 'bg-slate-100 text-slate-900'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    <div className="flex items-center space-x-1.5">
                                        <Wallet className="w-4 h-4 text-slate-500" />
                                        <span>Smeta (Byudjet)</span>
                                    </div>
                                </Link>
                            </nav>
                        </div>

                        {/* Current User & Logout */}
                        <div className="flex items-center space-x-3">
                            {currentUser && (
                                <div className="flex items-center space-x-2">
                                    <div className={`flex items-center px-2.5 py-1 rounded-full text-xs font-medium border ${getRoleColor(currentUser.role)}`}>
                                        <UserCheck className="w-3.5 h-3.5 mr-1" />
                                        <span>{currentUser.name}</span>
                                        <span className="ml-1 opacity-75">({currentUser.role_label})</span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => router.post('/logout')}
                                        title="Tizimdan chiqish"
                                        className="inline-flex items-center px-2.5 py-1 text-xs font-medium text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition border border-transparent hover:border-rose-200"
                                    >
                                        <LogOut className="w-3.5 h-3.5 mr-1" />
                                        <span>Chiqish</span>
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Notifications */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
                {flash?.success && (
                    <div className="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center space-x-2">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center space-x-2">
                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}
            </div>

            {/* Main Content */}
            <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full">
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
                <div className="max-w-7xl mx-auto px-4">
                    Yetkazib berish shartnomasi: Jarima va Buxgalteriya moduli • Laravel 12 + React + Tailwind
                </div>
            </footer>
        </div>
    );
}
