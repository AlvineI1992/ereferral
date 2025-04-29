import React, { useState, useEffect } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import {
  Pencil, Trash2, TableOfContents, Eye, CircleArrowRight
} from "lucide-react";
import { Inertia } from "@inertiajs/inertia";
import {
  Table, TableBody, TableCaption, TableCell,
  TableHead, TableHeader, TableRow
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu, DropdownMenuCheckboxItem,
  DropdownMenuContent, DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";


type ListProps = {
  refreshKey: any; 
  onEdit: (id: number) => void;
};

const Lists = ({ refreshKey, onEdit }: ListProps) => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const perPage = 10;
  const [visibleColumns, setVisibleColumns] = useState({
    id: true,
    name: true,
    status: true,
    remarks: true,
    actions: true,
  });

  const fetchData = async (pageNumber = 1, search = "") => {
    setLoading(true);
    try {
      const res = await axios.get(`/api/emr?page=${pageNumber}&search=${search}`);
      setData(res.data.data);
      setTotalRows(res.data.total);
    } catch (err) {
      console.error("Fetch error:", err);
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchData(page, searchTerm);
  }, [refreshKey, page, searchTerm]);

  const handleDelete = async (id:string) => {
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
        await axios.delete(`/roles/delete/${id}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });
        fetchData(page, searchTerm);
        Swal.fire({
          title: "Deleted!",
          text: "The role has been deleted.",
          icon: "success",
          timer: 1500,
          showConfirmButton: false,
        });
      } catch (err) {
        console.error("Delete error:", err);
        Swal.fire("Oops!", "Something went wrong.", "error");
      }
    }
  };

  const handleEdit = (row:string) => onEdit?.(row);

  const handleGoto = (id:string) => {
    if (!id) {
      console.error("ID parameter is required");
      return;
    }
    Inertia.visit(`/emr/profile/${id}`);
  };

  const totalPages = Math.ceil(totalRows / perPage);

  const toggleColumn = (col) => {
    setVisibleColumns((prev) => ({
      ...prev,
      [col]: !prev[col],
    }));
  };

  return (
    <div className="p-3 bg-white  mr-3 ml-3 mt-3">
      <div className="flex justify-end mb-2">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" className="flex items-center gap-1 p-1 text-xs cursor-pointer">
              <Eye size={16} /> Columns
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent>
            {Object.keys(visibleColumns).map((key) => (
              <DropdownMenuCheckboxItem
                key={key}
                checked={visibleColumns[key]}
                onCheckedChange={() => toggleColumn(key)}
              >
                {key.charAt(0).toUpperCase() + key.slice(1)}
              </DropdownMenuCheckboxItem>
            ))}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          <TableOfContents size="16" />
          <h2 className="text-sm font-semibold">Provider List</h2>
        </div>
        <Input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-44 p-1 text-xs"
        />
      </div>

      {loading ? (
        <div className="flex justify-center items-center py-4">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-xs text-blue-600">&nbsp;Please wait...</span>
        </div>
      ) : (
        <>
          <Table className="text-sm">
            <TableCaption>
              Showing {data.length} of {totalRows} provider(s)
            </TableCaption>
            <TableHeader>
              <TableRow>
                {visibleColumns.id && <TableHead className="p-1">ID</TableHead>}
                {visibleColumns.name && <TableHead className="p-1">Name</TableHead>}
                {visibleColumns.status && <TableHead className="p-1">Status</TableHead>}
                {visibleColumns.remarks && <TableHead className="p-1">Remarks</TableHead>}
                {visibleColumns.actions && <TableHead className="p-1 text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.length > 0 ? data.map((row) => (
                <TableRow key={row.emr_id}>
                  {visibleColumns.id && <TableCell className="p-1">{row.emr_id}</TableCell>}
                  {visibleColumns.name && <TableCell className="p-1">{row.emr_name}</TableCell>}
                  {visibleColumns.status && (
                    <TableCell className="p-1">
                      <span className={`px-1 py-0.5 rounded-full text-xs font-medium ${
                        row.status === "1"
                          ? "bg-green-100 text-green-700"
                          : "bg-red-100 text-red-700"
                      }`}>
                        {row.status === "1" ? "Active" : "Inactive"}
                      </span>
                    </TableCell>
                  )}
                  {visibleColumns.remarks && <TableCell className="p-1 max-w-xs truncate">{row.remarks}</TableCell>}
                  {visibleColumns.actions && (
                    <TableCell className="p-1 text-right flex justify-end space-x-1">
                      <Button variant="ghost" size="icon" onClick={() => handleEdit(row)} className="cursor-pointer">
                        <Pencil size={12} />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => handleDelete(row.emr_id)} className="cursor-pointer">
                        <Trash2 size={12} className="text-red-600" />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => handleGoto(row.emr_id)} className="cursor-pointer">
                        <CircleArrowRight size={12} className="text-blue-600" />
                      </Button>
                    </TableCell>
                  )}
                </TableRow>
              )) : (
                <TableRow>
                  <TableCell colSpan={5} className="text-center text-gray-500 italic py-4">
                    No results found.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>

          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 px-2 space-y-2 sm:space-y-0">
            <div className="text-xs text-gray-600">
              Page <span className="font-medium">{page}</span> of <span className="font-medium">{totalPages}</span> &nbsp;
              ({totalRows} {totalRows === 1 ? 'record' : 'records'})
            </div>

            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(p - 1, 1))}
                className="text-xs p-1 cursor-pointer"
              >
                Previous
              </Button>

              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .slice(Math.max(0, page - 3), Math.min(totalPages, page + 2))
                .map((pNum) => (
                  <Button
                    key={pNum}
                    variant={pNum === page ? "default" : "outline"}
                    className="px-3 py-1 text-xs cursor-pointer"
                    onClick={() => setPage(pNum)}
                  >
                    {pNum}
                  </Button>
                ))}

              <Button
                variant="outline"
                disabled={page >= totalPages}
                onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                className="text-xs p-1 cursor-pointer"
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
