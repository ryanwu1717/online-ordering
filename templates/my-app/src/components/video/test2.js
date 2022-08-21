import * as React from "react";
import { useTable } from "react-table";
import { useState, useEffect } from "react";

type Data = {
  actor: string;
  movie: string;
}

const borderStyle = {
  border: "1px dashed navy"
};

export default function App() {
  const origData = [
    {
      actor: "Johnny Depp",
      movies: [
        // {
        //   name: "Pirates of the Carribean 1"
        // },
        {
          name: "test"
        },
        {
          name: "Pirates of the Carribean 2"
        },
        {
          name: "Pirates of the Carribean 3"
        },
        {
          name: "Pirates of the Carribean 4"
        }
      ]
    }
  ];
  const newData: Array<Data> = [];
  origData.forEach(actorObj => {
    actorObj.movies.forEach(movie => {
      newData.push({
        actor: actorObj.actor,
        movie: movie.name
      });
    });
  });
  const data = React.useMemo(() => newData, []);
  const columns = React.useMemo(
    () => [
      {
        Header: "Actor",
        accessor: "actor"
      },
      {
        Header: "Movies",
        accessor: "movie"
      }
    ],
    []
  );
  const {
    getTableProps,
    getTableBodyProps,
    headerGroups,
    rows,
    prepareRow
  } = useTable({ columns, data });
  return (
    <table {...getTableProps()}>
      <thead>
        {headerGroups.map(headerGroup => (
          <tr {...headerGroup.getHeaderGroupProps()}>
            {headerGroup.headers.map(column => (
              <th {...column.getHeaderProps()} style={borderStyle}>
                {column.render("Header")}
              </th>
            ))}
          </tr>
        ))}
      </thead>
      <tbody {...getTableBodyProps()}>
        {rows.map((row, i) => {
          prepareRow(row);
          return (
            <tr {...row.getRowProps()} onClick={() => console.log(row.original)}>
              {row.cells.map((cell, j) => {
                return (
                  <td
                    rowSpan={cell.rowSpan}
                    {...cell.getCellProps()}
                    style={borderStyle}
                  >
                    {cell.render("Cell")}
                  </td>
                );
              })}
            </tr>
          );
        })}
      </tbody>
    </table>
  );
}