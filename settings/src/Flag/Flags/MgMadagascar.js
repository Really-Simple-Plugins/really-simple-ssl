import * as React from "react";
const SvgMgMadagascar = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MG_-_Madagascar_svg__a"
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
    <g
      fillRule="evenodd"
      clipRule="evenodd"
      mask="url(#MG_-_Madagascar_svg__a)"
    >
      <path fill="#78D843" d="M6 6h10v6H6V6Z" />
      <path fill="#EA1A1A" d="M6 0h10v6H6V0Z" />
      <path fill="#F7FCFF" d="M0 0h6v12H0V0Z" />
    </g>
  </svg>
);
export default SvgMgMadagascar;
