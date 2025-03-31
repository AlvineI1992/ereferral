import React from "react";
import datatable from "react-data-table-component";

const Table = ({ columns, data }) => {
    return <datatable columns={columns} data={data} pagination />;
};

export default Table;
