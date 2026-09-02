import React, { useState, useEffect } from "react";
import { Link, router } from '@inertiajs/react';
import {
  List,
  Hospital,
  Mars,
  Venus,
  Printer,
  ArrowRightIcon,
  Plus,
} from "lucide-react";
import axios from "axios";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import IncomingDashboard from "./IncomingDashboard";
import type { IncomingReferralRow, IncomingSummary, PermissionProps } from "./types";
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
} from "@/components/ui/avatar";

const emptySummary: IncomingSummary = {
  totalIncoming: 0,
  todayIncoming: 0,
  emergencyCount: 0,
  outpatientCount: 0,
  receivingFacilities: 0,
  topReasons: [],
  topProvinces: [],
  topCities: [],
  topBarangays: [],
  generatedAt: '',
};

const Lists = ({ canCreate, refreshKey, onEdit }: PermissionProps) => {
  const [data, setData] = useState<IncomingReferralRow[]>([]);
  const [summary, setSummary] = useState<IncomingSummary>(emptySummary);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const [perPage, setPerPage] = useState(5);

  const handleGoto = (id: string) => {
    if (!id) return;
    const encodedId = btoa(id.toString());
    router.visit(`/incoming/profile/${encodedId}`);
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      const fetchData = async () => {
        setLoading(true);
        try {
          const response = await axios.get(
            `/incoming/list?page=${page}&search=${searchTerm}&per_page=${perPage}`
          );
          setData(response.data.data ?? []);
          setTotalRows(response.data.total ?? 0);
          setSummary(response.data.summary ?? emptySummary);
        } catch (error) {
          console.error("Error fetching referrals:", error);
        } finally {
          setLoading(false);
        }
      };

      void fetchData();
    }, 500);

    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm, perPage]);

  const handleEdit = (row: IncomingReferralRow) => {
    onEdit?.(row);
  };

  const totalPages = Math.ceil(totalRows / perPage);

  return (
    <div className="flex w-full flex-col gap-6">
      <IncomingDashboard summary={summary} canCreate={!!canCreate} />

      <div className="w-full overflow-x-auto rounded-[1.75rem] border border-slate-200/80 bg-white/90 p-4 shadow-sm">
        <div className="mb-3 flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <List size={20} />
            <h2 className="text-xl font-semibold tracking-tight">Incoming Referrals</h2>
          </div>
          {canCreate && (
            <Link href="/referrals/create">
              <Button variant="outline">
                <Plus className="mr-2" /> Add Referral
              </Button>
            </Link>
          )}
        </div>

      <div className="mb-3 flex items-center justify-between">
        <div className="flex items-center gap-1">
          <label htmlFor="perPage">Rows per page:</label>
          <select
            id="perPage"
            value={perPage}
            onChange={(e) => {
              setPage(1);
              setPerPage(Number(e.target.value));
            }}
            className="border px-2 py-1 text-xs"
          >
            {[5, 10, 15, 25, 50].map((num) => (
              <option key={num} value={num}>{num}</option>
            ))}
          </select>
        </div>

        <Input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-64 text-sm"
        />
      </div>

      {/* Table Display */}
      {loading ? (
        <div className="flex justify-center items-center py-8">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="ml-2 text-sm text-blue-600">Loading referrals...</span>
        </div>
      ) : (
        <>
          <table className="min-w-full text-sm">
            <thead>
              <tr className="border-t mb-1">
                <th className="w-12 px-1 py-2 text-left">#</th>
                <th className="px-1 py-2 text-left">Patient</th>
                <th className="px-1 py-2 text-left">LogID</th>
                <th className="px-1 py-2 text-left">Referral Date</th>
                <th className="px-1 py-2 text-left">Origin</th>
                <th className="px-1 py-2 text-left">Destination</th>
                <th className="px-1 py-2 text-left">Type</th>
                <th className="px-1 py-2 text-left">Category</th>
                <th className="px-1 py-2 text-left">Reason</th>
                <th className="px-1 py-2 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.length > 0 ? (
                data.map((row) => (
                  <tr key={row.LogID}>
                    <td className="px-1 py-2 align-top font-medium text-slate-600">{row.index}</td>
                    <td className="px-1 py-2">
                      <div className="flex flex-col items-start gap-1">
                        <div className="flex items-center gap-1">
                          <Avatar className="w-8 h-8">
                            <AvatarImage src={row.avatar || "/default-avatar.jpg"} />
                            <AvatarFallback>
                              {row.patient_name?.charAt(0).toUpperCase()}
                              {row.patient_name?.charAt(1).toUpperCase()}
                            </AvatarFallback>
                          </Avatar>
                          <span className="text-sm">{row.patient_name}</span>
                        </div>
                        <div className="ml-10 text-[10px] space-y-1">
                          <div className="flex items-center gap-1">
                            <strong>Sex:</strong>
                            {row.patient_sex === "Male" ? (
                              <Mars className="text-blue-700" size={12} />
                            ) : (
                              <Venus className="text-pink-700" size={12} />
                            )}
                            <span
                              className={`${
                                row.patient_sex === "Male"
                                  ? "text-blue-700"
                                  : "text-pink-700"
                              }`}
                            >
                              {row.patient_sex}
                            </span>
                          </div>
                          <div><strong>Date of birth:</strong> {row.patient_birthdate}</div>
                          <div><strong>Civil status:</strong> {row.patient_civilstatus}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-1 py-2">{row.LogID}</td>
                    <td className="px-1 py-2">{row.referral_date} {row.referral_time}</td>
                    <td className="px-1 py-2">
                      <div className="flex items-center gap-1">
                        <Hospital size={12} />
                        <span className="text-[10px]">{row.referral_origin_name}</span>
                      </div>
                    </td>
                    <td className="px-1 py-2">
                      <div className="flex items-center gap-1">
                        <Hospital size={12} />
                        <span className="text-[10px]">{row.referral_destination_name}</span>
                      </div>
                    </td>
                    <td className="px-1 py-2 text-[10px]">{row.referral_type_description}</td>
                    <td className="px-1 py-2 text-[10px]">{row.referral_category}</td>
                    <td className="px-1 py-2 text-[10px]">{row.referral_reason_description}</td>
                    <td className="px-1 py-2">
                      <div className="flex gap-1 justify-center">
                        <Button
                          variant="outline"
                          size="icon"
                          onClick={() => handleEdit(row)}
                          className="group hover:bg-green-500"
                        >
                          <Printer size={16} className="text-blue-700 group-hover:text-white" />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          onClick={() => handleGoto(row.LogID)}
                          className="group hover:bg-green-700"
                        >
                          <ArrowRightIcon size={16} className="text-blue-700 group-hover:text-white" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={10} className="text-center text-gray-500 italic py-6">
                    No referrals found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Pagination */}
          <div className="flex justify-between items-center mt-4 text-xs text-gray-600">
            <span>
              Page <strong>{page}</strong> of <strong>{totalPages}</strong> ({totalRows} total)
            </span>
            <div className="flex gap-2">
              <Button
                variant="outline"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(p - 1, 1))}
                className="text-xs px-2 py-1"
              >
                Previous
              </Button>
              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .slice(Math.max(0, page - 3), Math.min(totalPages, page + 2))
                .map((pNum) => (
                  <Button
                    key={pNum}
                    variant={pNum === page ? "default" : "outline"}
                    className="text-xs px-3 py-1"
                    onClick={() => setPage(pNum)}
                  >
                    {pNum}
                  </Button>
                ))}
              <Button
                variant="outline"
                disabled={page >= totalPages}
                onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                className="text-xs px-2 py-1"
              >
                Next
              </Button>
            </div>
          </div>
        </>
      )}
      </div>
    </div>
  );
};

export default Lists;
