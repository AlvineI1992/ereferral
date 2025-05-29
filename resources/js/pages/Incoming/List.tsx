import React, { useState, useEffect, useRef } from "react";
import { Link } from '@inertiajs/react';
import {
  Pencil,
  Trash2,
  List,
  Hospital,
  Mars,
  Venus,
  Printer,
  ArrowRightIcon,
  Plus,
  QrCode,
  Scan,
} from "lucide-react";
import axios from "axios";
import Swal from "sweetalert2";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { PermissionProps } from "./types";
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
} from "@/components/ui/avatar";
import { Inertia } from "@inertiajs/inertia";
import { Html5Qrcode } from "html5-qrcode";

const Lists = ({ canEdit, canDelete, refreshKey, onEdit }: PermissionProps) => {
  const [scanned, setScanned] = useState<string | null>(null);
  const [isScanning, setIsScanning] = useState(false);
  const scannerRef = useRef<any>(null);
  const qrBoxRef = useRef(null);

  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const [perPage, setPerPage] = useState(5);

  const fetchData = async (pageNumber = 1, search = "") => {
    setLoading(true);
    try {
      const response = await axios.get(
        `/incoming/list?page=${pageNumber}&search=${search}&per_page=${perPage}`
      );
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching referrals:", error);
    }
    setLoading(false);
  };

  const handleGoto = (id: string) => {
    if (!id) return;
    const encodedId = btoa(id.toString());
    Inertia.visit(`/incoming/profile/${encodedId}`);
  };

  const startScanner = async () => {
    if (isScanning) return;
    setScanned(null);
    setIsScanning(true);

    const html5QrCode = new Html5Qrcode("qr-reader");
    scannerRef.current = html5QrCode;

    try {
      await html5QrCode.start(
        { facingMode: "environment" },
        {
          fps: 10,
          qrbox: { width: 250, height: 250 },
        },
        (decodedText: string) => {
          html5QrCode.stop();
          setIsScanning(false);
          setScanned(decodedText);
          const encodedId = btoa(decodedText.trim());
          Inertia.visit(`/incoming/profile/${encodedId}`);
        },
        (errorMessage: string) => {
          // optional: console.log("QR Scan error", errorMessage);
        }
      );
    } catch (err: any) {
      console.error("Failed to start camera:", err);
      Swal.fire("Camera Error", err.message || "Failed to access camera.", "error");
      setIsScanning(false);
    }
  };

  const stopScanner = () => {
    if (scannerRef.current) {
      scannerRef.current.stop().then(() => {
        setIsScanning(false);
      });
    }
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchData(page, searchTerm);
    }, 500);
    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm, perPage]);

  const handleDelete = async (id: number) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, delete it!",
    });

    if (result.isConfirmed) {
      try {
        await axios.delete(`/permission/delete/${id}`);
        fetchData(page, searchTerm);
        Swal.fire("Deleted!", "The record has been deleted.", "success");
      } catch (error) {
        console.error("Error deleting record:", error);
        Swal.fire("Oops!", "Something went wrong.", "error");
      }
    }
  };

  const handleEdit = (row: any) => {
    onEdit?.(row);
  };

  const totalPages = Math.ceil(totalRows / perPage);

  return (
    <div className="p-4 w-full h-full overflow-x-auto">
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center space-x-2">
          <List size={20} />
          <h2 className="text-xl">Incoming Referrals</h2>
        </div>
        <Link href="/referrals/create">
          <Button variant="outline">
            <Plus className="mr-2" /> Add Referral
          </Button>
      </Link>

        
      </div>

      

      <div className="flex justify-between items-center mb-3">
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
                data.map((row: any) => (
                  <tr key={row.LogID}>
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
                  <td colSpan={9} className="text-center text-gray-500 italic py-6">
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
  );
};

export default Lists;
