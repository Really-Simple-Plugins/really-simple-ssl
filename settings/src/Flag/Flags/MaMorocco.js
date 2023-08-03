import * as React from "react";
const SvgMaMorocco = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MA_-_Morocco_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#MA_-_Morocco_svg__a)">
      <path fill="#C51918" d="M0 0h16v11a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V0Z" />
      <path fill="#E31D1C" d="M0 0h16v12H0V0Z" />
      <path
        fill="#579D20"
        d="M11.241 9.813 8.083 1.35h-.137L4.898 9.813 8.063 7.91l3.178 1.903ZM7.733 4.111l.339-1.32.351 1.358 1.01 2.828.592 1.37-1.28-.902-.682-.41-.673.405-1.25.906.578-1.397 1.015-2.838Z"
      />
      <path
        fill="#579D20"
        d="M6.331 6.737 8.08 7.899 9.71 6.737l3.098-2.619H3.193L6.33 6.737Zm.037-1-1.19-.727h5.613l-1.021.646-1.698 1.288-1.704-1.207Z"
      />
    </g>
  </svg>
);
export default SvgMaMorocco;
