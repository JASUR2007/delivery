import React, { useState } from 'react';
import { usePage, useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { 
    Plus, 
    Calendar, 
    Package, 
    ArrowRight, 
    X, 
    FileText, 
    CheckCircle, 
    Clock, 
    AlertTriangle 
} from 'lucide-react';

export default function ContractsIndex({ contracts = [] }) {
    const { auth } = usePage().props;
    const isExecutor = auth?.user?.role === 'executor';

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        contract_number: '',
        supplier_name: '',
        start_date: new Date().toISOString().slice(0, 10),
        duration_days: 10,
        items: [
            {
                item_name: '',
                paid_quantity: 1000,
                bonus_quantity: 50,
                unit_price: '2.50'
            }
        ]
    });

    const handleAddItem = () => {
        setData('items', [
            ...data.items,
            { item_name: '', paid_quantity: 100, bonus_quantity: 0, unit_price: '1.00' }
        ]);
    };

    const handleRemoveItem = (index) => {
        if (data.items.length === 1) return;
        const newItems = [...data.items];
        newItems.splice(index, 1);
        setData('items', newItems);
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...data.items];
        newItems[index][field] = value;
        setData('items', newItems);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/contracts', {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            }
        });
    };

    const getStatusBadge = (status, label) => {
        switch (status) {
            case 'draft':
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                        <Clock className="w-3 h-3 mr-1 text-slate-500" />
                        {label}
                    </span>
                );
            case 'active':
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        <CheckCircle className="w-3 h-3 mr-1 text-blue-600" />
                        {label}
                    </span>
                );
            case 'completed':
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <CheckCircle className="w-3 h-3 mr-1 text-emerald-600" />
                        {label}
                    </span>
                );
            default:
                return null;
        }
    };

    return (
        <AppLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-slate-900 tracking-tight">Shartnomalar ro‘yxati</h2>
                        <p className="text-sm text-slate-500 mt-1">
                            Yetkazib berish shartnomalari, tovarlar partiyasi, hisoblangan jarimalar va to‘lov holati
                        </p>
                    </div>

                    {isExecutor && (
                        <button
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-xs text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition"
                        >
                            <Plus className="w-4 h-4 mr-1.5" />
                            Yangi shartnoma
                        </button>
                    )}
                </div>

                {/* Contracts Table */}
                <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Shartnoma raqami
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Yetkazuvchi
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Muddat
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Pullik summa
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Yetkazilgan tovar
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Holat
                                    </th>
                                    <th className="px-6 py-3.5 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Amallar
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {contracts.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="px-6 py-12 text-center text-slate-500">
                                            Hozircha hech qanday shartnoma topilmadi.
                                        </td>
                                    </tr>
                                ) : (
                                    contracts.map((contract) => (
                                        <tr key={contract.id} className="hover:bg-slate-50 transition">
                                            <td className="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                                <div className="flex items-center space-x-2">
                                                    <FileText className="w-4 h-4 text-indigo-500" />
                                                    <span>{contract.contract_number}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                                {contract.supplier_name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                                <div className="text-xs">
                                                    <div>Boshlanish: {contract.start_date}</div>
                                                    <div className="text-slate-500">Muddat: {contract.deadline_date} ({contract.duration_days} kun)</div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">
                                                {contract.total_paid_amount} so‘m
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                                <div className="flex flex-col text-xs">
                                                    <span>
                                                        Pullik: {contract.delivered_paid_quantity} / {contract.total_paid_quantity} dona
                                                    </span>
                                                    {contract.total_bonus_quantity > 0 && (
                                                        <span className="text-emerald-600">
                                                            Bonus: {contract.delivered_bonus_quantity} / {contract.total_bonus_quantity} dona
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(contract.status, contract.status_label)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <Link
                                                    href={`/contracts/${contract.id}`}
                                                    className="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-900 transition"
                                                >
                                                    <span>Ochish</span>
                                                    <ArrowRight className="w-3.5 h-3.5 ml-1" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Modal: Create Contract */}
                {isCreateModalOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl max-w-3xl w-full p-6 space-y-6">
                            <div className="flex justify-between items-center border-b border-slate-100 pb-4">
                                <h3 className="text-lg font-bold text-slate-900">Yangi shartnoma kiritish</h3>
                                <button
                                    onClick={() => setIsCreateModalOpen(false)}
                                    className="text-slate-400 hover:text-slate-600 transition"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-5">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Shartnoma raqami (noyob)
                                        </label>
                                        <input
                                            type="text"
                                            value={data.contract_number}
                                            onChange={(e) => setData('contract_number', e.target.value)}
                                            placeholder="Masalan: SH-2026-004"
                                            className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.contract_number && (
                                            <p className="text-xs text-rose-600 mt-1">{errors.contract_number}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Yetkazib beruvchi tashkilot
                                        </label>
                                        <input
                                            type="text"
                                            value={data.supplier_name}
                                            onChange={(e) => setData('supplier_name', e.target.value)}
                                            placeholder="Masalan: Farm Med Servis MCHJ"
                                            className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.supplier_name && (
                                            <p className="text-xs text-rose-600 mt-1">{errors.supplier_name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Boshlanish sanasi
                                        </label>
                                        <input
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.start_date && (
                                            <p className="text-xs text-rose-600 mt-1">{errors.start_date}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Muddat (kalendar kunlarda)
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            value={data.duration_days}
                                            onChange={(e) => setData('duration_days', parseInt(e.target.value) || 1)}
                                            className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.duration_days && (
                                            <p className="text-xs text-rose-600 mt-1">{errors.duration_days}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Items Section */}
                                <div className="space-y-3 pt-2">
                                    <div className="flex justify-between items-center">
                                        <label className="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                            Tovar qatorlari
                                        </label>
                                        <button
                                            type="button"
                                            onClick={handleAddItem}
                                            className="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                        >
                                            + Qator qo‘shish
                                        </button>
                                    </div>

                                    <div className="space-y-3">
                                        {data.items.map((item, index) => (
                                            <div key={index} className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xs font-bold text-slate-600">Tovar #{index + 1}</span>
                                                    {data.items.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleRemoveItem(index)}
                                                            className="text-rose-500 hover:text-rose-700 text-xs"
                                                        >
                                                            O‘chirish
                                                        </button>
                                                    )}
                                                </div>

                                                <div className="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                                    <div className="sm:col-span-2">
                                                        <input
                                                            type="text"
                                                            placeholder="Tovar nomi"
                                                            value={item.item_name}
                                                            onChange={(e) => handleItemChange(index, 'item_name', e.target.value)}
                                                            className="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-md"
                                                            required
                                                        />
                                                    </div>
                                                    <div>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            placeholder="Pullik soni"
                                                            value={item.paid_quantity}
                                                            onChange={(e) => handleItemChange(index, 'paid_quantity', parseInt(e.target.value) || 0)}
                                                            className="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-md"
                                                            required
                                                        />
                                                    </div>
                                                    <div>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            placeholder="Bonus soni"
                                                            value={item.bonus_quantity}
                                                            onChange={(e) => handleItemChange(index, 'bonus_quantity', parseInt(e.target.value) || 0)}
                                                            className="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-md"
                                                        />
                                                    </div>
                                                    <div>
                                                        <input
                                                            type="text"
                                                            placeholder="Narxi (so‘m)"
                                                            value={item.unit_price}
                                                            onChange={(e) => handleItemChange(index, 'unit_price', e.target.value)}
                                                            className="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-md"
                                                            required
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setIsCreateModalOpen(false)}
                                        className="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition disabled:opacity-50"
                                    >
                                        {processing ? 'Saqlanmoqda...' : 'Shartnomani yaratish'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
