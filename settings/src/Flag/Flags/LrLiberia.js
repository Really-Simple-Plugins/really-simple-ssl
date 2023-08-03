import * as React from "react";
const SvgLrLiberia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LR_-_Liberia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#LR_-_Liberia_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path fill="#E31D1C" d="M.014 2.75h16v1.5h-16z" />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0h16v1.5H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#E31D1C"
        d="M-.029 5.5h16V7h-16zM.056 8.2h16v1.5h-16zM.051 10.75h16v1.5h-16z"
      />
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0h8v7H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M4.066 4.607 2.606 5.62l.466-1.736L2 2.776l1.452-.06L4.066 1l.615 1.716H6.13L5.06 3.884l.536 1.633-1.53-.91Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgLrLiberia;
