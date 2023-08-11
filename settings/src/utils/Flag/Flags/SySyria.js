import * as React from "react";
const SvgSySyria = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SY_-_Syria_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#SY_-_Syria_svg__a)">
      <path fill="#F7FCFF" d="M0 0h16v12H0V0Z" />
      <path
        fill="#409100"
        d="m4.5 6.935-.934.565.213-1.102L3 5.573l1.055-.044L4.5 4.5l.446 1.029H6l-.777.87.234 1.101-.956-.565ZM11.5 6.935l-.934.565.213-1.102L10 5.573l1.055-.044L11.5 4.5l.446 1.029H13l-.777.87.234 1.101-.956-.565Z"
      />
      <path fill="#E31D1C" d="M0 0v4h16V0H0Z" />
      <path fill="#272727" d="M0 8v4h16V8H0Z" />
    </g>
  </svg>
);
export default SvgSySyria;
