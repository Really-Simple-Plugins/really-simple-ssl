import * as React from "react";
const SvgSnSenegal = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SN_-_Senegal_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#SN_-_Senegal_svg__a)">
      <path fill="#FBCD17" d="M5 0h6v12H5V0Z" />
      <path
        fill="#006923"
        d="m8.038 7.245-1.743 1.21.557-2.071-1.28-1.323 1.733-.072.733-2.047.733 2.047h1.73L9.223 6.384l.639 1.948-1.825-1.087Z"
      />
      <path fill="#E11C1B" d="M11 0h5v12h-5V0Z" />
      <path fill="#006923" d="M0 0h5v12H0V0Z" />
    </g>
  </svg>
);
export default SvgSnSenegal;
