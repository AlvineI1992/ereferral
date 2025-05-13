import React, { useState, useEffect } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import {
  Pencil, Trash2, TableOfContents, Eye
} from "lucide-react";
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

type ListProps = {
  refreshKey: any;
  id: string | null;
};

const Reference_List = ({ refreshKey, id }: ListProps) => {
  const [data, setData] = useState<any[]>([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [totalRows, setTotalRows] = useState(0);
  const [selectedRows, setSelectedRows] = useState<string[]>([]);
  const [lastCheckedIndex, setLastCheckedIndex] = useState<number | null>(null);
  const [sortConfig, setSortConfig] = useState<{ key: string; direction: 'asc' | 'desc' } | null>(null);

  const [visibleColumns, setVisibleColumns] = useState({
    id: true,
    name: true,
    status: true,
    remarks: true,
    actions: true,
    checkbox: true,
  });

  const getAbbreviation = (name: string) =>
    name.split(' ').map(word => word[0]).join('').toUpperCase();

  const fetchData = async (pageNumber = 1, search = "", emr_id = id) => {
    setLoading(true);
    try {
      const params: any = { page: pageNumber, search, perPage };
      if (emr_id) params.emr_id = emr_id;
      const res = await axios.get("/facility/list/", { params });
      setData(res.data.data);
      setTotalRows(res.data.total);
    } catch (err) {
      console.error("Fetch error:", err);
      Swal.fire("Error", "Failed to fetch facilities.", "error");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData(page, searchTerm);
    setSelectedRows([]);
  }, [refreshKey, page, searchTerm, perPage]);

  // Sync parent id filter when id changes
  useEffect(() => {
    fetchData(1, searchTerm, id);
  }, [id]);

  const handleSort = (key: string) => {
    let direction: 'asc' | 'desc' = 'asc';
    if (sortConfig?.key === key && sortConfig.direction === 'asc') direction = 'desc';
    setSortConfig({ key, direction });
  };

  const sortedData = [...data].sort((a, b) => {
    if (!sortConfig) return 0;
    const aVal = a[sortConfig.key];
    const bVal = b[sortConfig.key];
    return sortConfig.direction === 'asc'
      ? aVal.localeCompare(bVal)
      : bVal.localeCompare(aVal);
  });

  const handleCheckboxChange = (hfhudcode: string, index: number, event: React.ChangeEvent<HTMLInputElement>) => {
    let newSelected = [...selectedRows];
    if (event.shiftKey && lastCheckedIndex !== null) {
      const start = Math.min(lastCheckedIndex, index);
      const end = Math.max(lastCheckedIndex, index);
      const range = data.slice(start, end + 1).map(r => r.hfhudcode);
      if (newSelected.includes(hfhudcode)) {
        newSelected = newSelected.filter(id => !range.includes(id));
      } else {
        range.forEach(code => { if (!newSelected.includes(code)) newSelected.push(code); });
      }
    } else {
      if (newSelected.includes(hfhudcode)) {
        newSelected = newSelected.filter(id => id !== hfhudcode);
      } else {
        newSelected.push(hfhudcode);
      }
      setLastCheckedIndex(index);
    }
    setSelectedRows(newSelected);
  };

  const handleSelectAll = () => {
    if (selectedRows.length === data.length) setSelectedRows([]);
    else setSelectedRows(data.map(r => r.hfhudcode));
  };

  const handleBulkDelete = async () => {
    if (!id) return;
    try {
      await axios.post("/emr/revoke", {  emr_id: id, facilities: selectedRows  });
      Swal.fire("Removed", `${selectedRows.length} facility(ies) removed.`, "success");
      fetchData(page, searchTerm, id);
      setSelectedRows([]);
    } catch (err) {
      console.error("Error removing facilities:", err);
      Swal.fire("Error", "Failed to remove facilities.", "error");
    }
  };

  const toggleColumn = (col: string) => {
    setVisibleColumns(prev => ({ ...prev, [col]: !prev[col] }));
  };

  const totalPages = Math.ceil(totalRows / perPage);

  return (
    <div className="p-3 mr-2 ml-2 mt-1">
      <div className="flex justify-between mb-2">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" className="flex items-center gap-1 p-1 text-xs">
              <Eye size={16} /> Columns
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent>
            {Object.keys(visibleColumns).map(key => (
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

        <div className="flex gap-2">
          <div className="text-xs">
            Show
            <select
              value={perPage}
              onChange={e => { setPage(1); setPerPage(Number(e.target.value)); }}
              className="ml-2 border px-1 py-0.5 rounded text-xs"
            >
              {[10,25,50,100].map(size => <option key={size} value={size}>{size}</option>)}
            </select>
            &nbsp;entries
          </div>
        </div>
      </div>

      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          <TableOfContents size="16" />
          <h2 className="text-lg">Assigned Facilities</h2>
        </div>
        <Input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={e => setSearchTerm(e.target.value)}
          className="w-44 p-1 text-xs"
        />
      </div>

      {selectedRows.length > 0 && (
        <div className="mb-3 text-xs text-red-600 flex justify-between items-center">
          <span>{selectedRows.length} selected</span>
          <Button variant="destructive" size="sm" className="text-xs px-2 py-1" onClick={handleBulkDelete}>
            Remove
          </Button>
        </div>
      )}

      {loading ? (
        <div className="flex justify-center items-center py-4">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-xs text-blue-600">&nbsp;Please wait...</span>
        </div>
      ) : (
        <>
          <div className="overflow-x-auto">
            <Table className="text-sm">
              <TableCaption>Showing {data.length} of {totalRows} facility(s)</TableCaption>
              <TableHeader>
                <TableRow>
                  {visibleColumns.checkbox && <TableHead className="p-1"><input type="checkbox" checked={selectedRows.length===data.length && data.length>0} onChange={handleSelectAll} /></TableHead>}
                  {visibleColumns.id && <TableHead className="p-1 cursor-pointer" onClick={()=>handleSort('hfhudcode')}>ID</TableHead>}
                  {visibleColumns.name && <TableHead className="p-1 cursor-pointer" onClick={()=>handleSort('facility_name')}>Name</TableHead>}
                  {visibleColumns.name && <TableHead className="p-1">Abbrv</TableHead>}
                  {visibleColumns.name && <TableHead className="p-1">Type</TableHead>}
                  {visibleColumns.name && <TableHead className="p-1">Region</TableHead>}
                </TableRow>
              </TableHeader>
              <TableBody>
                {sortedData.length>0 ? sortedData.map((row, index)=> (
                  <TableRow key={row.hfhudcode}>
                    {visibleColumns.checkbox && <TableCell className="p-1"><input type="checkbox" checked={selectedRows.includes(row.hfhudcode)} onChange={e=>handleCheckboxChange(row.hfhudcode,index,e)} /></TableCell>}
                    {visibleColumns.id && <TableCell className="p-1">{row.hfhudcode}</TableCell>}
                    {visibleColumns.name && <TableCell className="p-1">{row.facility_name}</TableCell>}
                    {visibleColumns.name && <TableCell className="p-1">{getAbbreviation(row.facility_name)}</TableCell>}
                    {visibleColumns.name && <TableCell className="p-1">{row.description}</TableCell>}
                    {visibleColumns.name && <TableCell className="p-1">{row.regname}</TableCell>}
                  </TableRow>
                )) : (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-gray-500 italic py-4">No results found.</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>

          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 px-2 space-y-2 sm:space-y-0">
            <div className="text-xs text-gray-600">Page <span className="font-medium">{page}</span> of <span className="font-medium">{totalPages}</span> ({totalRows} {totalRows===1?'record':'records'})</div>
            <div className="flex items-center gap-2">
              <Button variant="outline" disabled={page<=1} onClick={()=>setPage(p=>Math.max(p-1,1))} className="text-xs p-1">Previous</Button>
              {Array.from({length:totalPages},(_,i)=>i+1).slice(Math.max(0,page-3),Math.min(totalPages,page+2)).map(pNum=>(
                <Button key={pNum} variant={pNum===page?"default":"outline"} className="px-3 py-1 text-xs" onClick={()=>setPage(pNum)}>{pNum}</Button>
              ))}
              <Button variant="outline" disabled={page>=totalPages} onClick={()=>setPage(p=>Math.min(p+1,totalPages))} className="text-xs p-1">Next</Button>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default Reference_List;
