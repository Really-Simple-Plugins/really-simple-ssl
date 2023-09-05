import * as React from "react";
const SvgMlMali = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="ML_-_Mali_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <rect width={16} height={12} fill="#fff" rx={0} />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#ML_-_Mali_svg__a)">
      <path fill="#FBCD17" d="M5 0h6v12H5V0Z" />
      <path fill="#E11C1B" d="M11 0h5v12h-5V0Z" />
      <path fill="#0B9E7A" d="M0 0h5v12H0V0Z" />
    </g>
  </svg>
);
export default SvgMlMali;
