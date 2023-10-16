import * as React from "react";
const SvgGfFrenchGuiana = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GF_-_French_Guiana_svg__a"
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
    <g mask="url(#GF_-_French_Guiana_svg__a)">
      <rect width={16} height={12} fill="#5EAA22" rx={1} />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="m0 0 16 12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#E21835"
        fillRule="evenodd"
        d="M7.965 7.203 6.223 8.412l.556-2.07L5.5 5.019l1.732-.072.733-2.047.733 2.047h1.73L9.15 6.342l.64 1.948-1.826-1.087Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgGfFrenchGuiana;
